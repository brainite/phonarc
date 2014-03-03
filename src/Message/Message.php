<?php
namespace Witti\Phonarc\Message;
use Doctrine\ORM\Mapping\ClassMetadata;

class Message {
  protected $id;
  protected $date_sent;
  protected $thread_md5;
  protected $subject;
  protected $from_email;
  protected $from_name;
  protected $thread_id;
  protected $parent_id;
  protected $body;
  protected $headers;

  public static function loadMetadata(ClassMetadata $metadata) {
    $metadata->mapField(array(
      'id' => true,
      'fieldName' => 'id',
      'type' => 'integer'
    ));
    $metadata->mapField(array(
      'fieldName' => 'date_sent',
      'type' => 'datetime'
    ));
    $metadata->mapField(array(
      'fieldName' => 'thread_md5',
      'length' => 32,
    ));
    $metadata->mapField(array(
      'fieldName' => 'subject',
      'length' => 255,
    ));
    $metadata->mapField(array(
      'fieldName' => 'from_email',
      'length' => 255,
    ));
    $metadata->mapField(array(
      'fieldName' => 'from_name',
      'length' => 255,
    ));
    $metadata->mapField(array(
      'fieldName' => 'thread_id',
      'type' => 'integer'
    ));
    $metadata->mapField(array(
      'fieldName' => 'parent_id',
      'type' => 'integer'
    ));
    $metadata->mapField(array(
      'fieldName' => 'body',
      'type' => 'text'
    ));
    $metadata->mapField(array(
      'fieldName' => 'headers',
      'type' => 'object'
    ));
    $metadata->setPrimaryTable(array(
      'name' => 'Message',
      'indexes' => array(
        'subject' => array(
          'columns' => array(
            'subject',
          ),
        ),
        'date_sent' => array(
          'columns' => array(
            'date_sent',
          ),
        ),
        'thread_md5' => array(
          'columns' => array(
            'thread_md5',
          ),
        ),
      ),
      'uniqueConstraints' => array(),
    ));
  }

}