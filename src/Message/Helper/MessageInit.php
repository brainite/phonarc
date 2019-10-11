<?php
/*
 * This file is part of the Phonarc package.
 *
 * (c) CPNP
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phonarc\Message\Helper;

use Phonarc\Context\PhonarcContext;
use Phonarc\Message\Message;
use QuipXml\Quip;

class MessageInit {
  static public function process(PhonarcContext &$context, Message &$message) {
    static $protect = array(
      '&' => '&amp;',
    );

    // Update the context version for this message.
    $message->setContextVersion($context->getConf('message.version'));

    // Locate the original message file.
    $path = $context->getConf('basepath') . '/' . $message->getMhonarcMessage();
    $path = realpath($path);
    if (!$path) {
      throw new \ErrorException("Unable to locate message: "
        . $message->getMhonarcMessage());
    }

    // Load the file.
    $html = file_get_contents($path);

    // Extract a part from the HTML.
    $extract = function ($start = FALSE, $stop = FALSE) use ($html) {
      if ($start === FALSE) {
        $tmp = array(
          '',
          $html,
        );
      }
      else {
        $tmp = explode("<!--$start-->", $html, 2);
      }
      if (isset($tmp[1])) {
        if ($stop === FALSE) {
          return $tmp[1];
        }
        $tmp = explode("<!--$stop-->", $tmp[1], 2);
        if (isset($tmp[1])) {
          return trim($tmp[0]);
        }
      }
      return '';
    };

    // Extract the meta data.
    $meta = array();
    $quip = Quip::load($path, 0, TRUE, '', FALSE, Quip::LOAD_NS_UNWRAP
      | Quip::LOAD_IGNORE_ERRORS);
    foreach ($quip->xpath("//meta") as $m) {
      $meta[strtoupper(trim($m['name']))] = trim($m['content']);
    }

    // Update the message with metadata.
    $message->setSubject($meta['SUBJECTNA']);
    $message->setDateSent(date_create($meta['MSGLOCALDATE']));
    $message->setFromEmail($meta['FROMADDR']);
    $message->setFromName($meta['FROMNAME']);
    $message->setMhonarcThread($meta['TTOP']);

    // Get the MHonArc version
    if (preg_match('/<!-- MHonArc v([^ ]*) -->/', $html, $arr)) {
      $data['mhonarcversion'] = $arr[1];
    }
    else {
      $data['mhonarcversion'] = 0;
    }

    // Extract and clean the body.
    $body = $extract('X-Body-of-Message', 'X-Body-of-Message-End');

    // Detect and address UTF-7. This catches when there are 4+ "<" characters.
    // mhonarc does not support:
    //    https://www.mhonarc.org/archive/html/mhonarc-dev/2008-02/msg00001.html
    // decoding:
    //    https://en.wikipedia.org/wiki/UTF-7#Decoding
    if (substr_count($body, '+ADw-') > 4) {
      $body = preg_replace_callback('@\+([A-Za-z0-9]{2,})\-@s', function ($matches) {
        $code = $matches[1];
        if ($code{0} === 'A') {
          // Handle the basic ASCII characters.
          $tmp = trim(base64_decode($code));
          if ($tmp !== '') {
            $tmp = str_replace("\0", '', $tmp);
            return $tmp;
          }
        }
        else {
          // All higher codes will be treated as entities.
          static $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+,';
          $bin = '';
          foreach (str_split($code) as $chr) {
            $b = decbin(strpos($charset, $chr));
            $bin .= str_pad($b, 6, '0', STR_PAD_LEFT);
          }
          $utf16 = '';
          $tmp = $bin;
          while (strlen($tmp) >= 16) {
            $b = substr($tmp, 0, 16);
            $tmp = substr($tmp, 16);
            $utf16 .= dechex(bindec($b));
          }
          return "&#x$utf16;";
        }
        return "+$code-";
      }, $body);
      // Aggressively sanitize UTF-7 messages
      $tmp = explode('<!--', $body);
      $body = array_shift($tmp);
      foreach ($tmp as $p) {
        $p = explode('-->', $p, 2);
        $body .= $p[1];
      }
      $body = strip_tags($body);
      $body = trim($body);
      $body = nl2br($body);
    }

    // Clean the body content by removing some characters.
    $body = strtr($body, $protect);
    $body = strtr($body, array(
      "\r" => '',
    ));
    $body = strtr($body, array_flip($protect));
    $data['body'] = &$body;
    $message->setBody($body);

    // Calculate the thread_id
    //     $thread_message = MessageRepository::findOneMessageBy(array(
    //       'mhonarc_message' => $meta['TTOP'],
    //     ));
    //     $thread_id = $thread_message->getThreadId();
    $message->setThreadId(MessageQuery::getIdFromMhonarcMessage($meta['TTOP']));
    $message->setParentId(MessageQuery::getIdFromMhonarcMessage($meta['TPARENT']));

    // Store all of the meta data in the database.
    $top = $extract(FALSE, 'X-Head-End');
    $headers = explode('<!--', $top);
    array_shift($headers);
    foreach ($headers as $header) {
      $h = explode('-->', $header);
      array_pop($h);
      $h = implode('-->', $h);
      $h = explode(':', $h, 2);
      $name = strtoupper(trim($h[0]));
      $content = isset($h[1]) ? trim($h[1]) : '';
      if (!isset($meta[$name])) {
        $meta[$name] = $content;
      }
      else {
        $meta[$name] = (array) $meta[$name];
        $meta[$name][] = $content;
      }
    }
    $message->setHeaders($meta);

    // Default values to allow save and/or trigger sync.
    $syncThreadId = (int) $message->getSyncThreadId();
    if (!$syncThreadId
      || ($message->getMhonarcMessage() != $message->getMhonarcThread())) {
      // Recompute thread id.
      $thread_message = MessageRepository::findById($message->getMhonarcThread());
      if ($thread_message) {
        $syncThreadId = (int) $thread_message->getSyncMessageId();
      }
    }
    $message->setSyncThreadId($syncThreadId);
    $message->setSyncMessageId((int) $message->getSyncMessageId());
    $message->setSyncContextVersion('pending');
  }

}