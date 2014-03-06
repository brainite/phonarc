<?php
namespace Phonarc\Message\Helper;

use Phonarc\Context\PhonarcContext;
use Phonarc\Message\Message;

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

    // Extract key sections from the MHonarc HTML.
    $data = array();
    $parts = array();
    $parts['headers_comments'] = $extract(FALSE, 'X-Head-End');

    // Extract the headers provided in HTML.
    $parts['headers_html'] = $extract('X-Head-of-Message', 'X-Head-of-Message-End');
    $xml = simplexml_load_string($parts['headers_html']);
    $key = NULL;
    foreach ($xml->children() as $child) {
      switch ($child->getName()) {
        case 'dt':
          $key = strtolower(trim($child[0]));
          break;
        case 'dd':
          $data[$key] = trim($child->asXml());
          $data[$key] = preg_replace('@^<dd.*?>|</dd>$@s', '', $data[$key]);
          break;
      }
    }
    unset($xml);

    // Extract the from data.
    if (preg_match('@^(.*?)(?:&lt;)?<a.*?>(.*?)</a>@s', $data['from'], $arr)) {
      $data['from_name'] = trim($arr[1], ' "\'');
      $data['from_email'] = $arr[2];
    }
    elseif (preg_match('@^.+@.+$@', $data['from'])) {
      $data['from_email'] = $data['from'];
      $data['from_name'] = 'unknown';
    }
    else {
      $data['from_name'] = $data['from'];
      $data['from_email'] = 'unknown';
    }

    // Select the thread.
    if (!isset($data['thread-index'])) {
      $data['thread-index'] = md5(microtime());
    }

    // Update the message with metadata.
    $message->setSubject($data['subject']);
    $message->setDateSent(date_create($data['date']));
    $message->setFromEmail($data['from_email']);
    $message->setFromName($data['from_name']);
    $message->setMhonarcThread($data['thread-index']);

    // Get the MHonArc version
    if (preg_match('/<!-- MHonArc v([^ ]*) -->/', $html, $arr)) {
      $data['mhonarcversion'] = $arr[1];
    }
    else {
      $data['mhonarcversion'] = 0;
    }

    // Extract and clean the body.
    $body = $extract('X-Body-of-Message', 'X-Body-of-Message-End');
    $body = strtr($body, $protect);
    $body = strtr($body, array(
      "\r" => '',
    ));
    // Apply cleaning here.
    $body = strtr($body, array_flip($protect));
    $data['body'] = &$body;
    $message->setBody($body);

    /*
     * @todo The follow options are still pending.
     */
    $headers = array();
    $message->setThreadId(0);
    $message->setParentId(0);
    $message->setHeaders($headers);
  }
}