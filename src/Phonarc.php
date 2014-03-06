<?php
/*
 * This file is part of the FileConverter package.
 *
 * (c) Greg Payne
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phonarc;
class Phonarc {
  public function factory($conf = NULL) {
    $phonarc = new Phonarc();
    $phonarc->setConf($conf);
    return $phonarc;
  }

  protected $settings = array(
    'getmail.options.type' => array(
      'description' => 'Getmail retriever type.',
      'value' => 'BrokenUIDLPOP3Retriever',
    ),
    'getmail.options..delete' => array(
      'description' => 'Delete the email after downloading.',
      'value' => FALSE,
    ),
    'getmail.options..delete_after' => array(
      'description' => 'Delete the email X days after downloading.',
      'value' => 0,
    ),
    'getmail.options..delete_bigger_than' => array(
      'description' => 'Delete the email if it is bigger than X bytes.',
      'value' => 0,
    ),
    'getmail.options..max_messages_per_session' => array(
      'description' => 'Maximum number of messages to download per session.',
      'value' => 0,
    ),
    'getmail.retriever.server' => array(
      'description' => 'Email server.',
      'value' => NULL,
    ),
    'getmail.retriever.username' => array(
      'description' => 'Email user.',
      'value' => NULL,
    ),
    'getmail.retriever.password' => array(
      'description' => 'Email password.',
      'value' => NULL,
    ),

  );

  public function downloadEmail() {
    // @todo NOT YET IMPLEMENTED
    return $this;
  }

  public function setConf($k, $v = NULL) {
    if (is_string($k)) {
      if (isset($this->settings[$k])) {
        $this->settings[$k]['value'] = $v;
      }
    }
    elseif (!isset($v) && is_array($k)) {
      foreach ((array) $k as $k1 => $v1) {
        $this->setting($k1, $v1);
      }
    }
    return $this;
  }

}