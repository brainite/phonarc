<?php
namespace Witti\Phonarc\Message;
use Doctrine\ORM\Mapping\ClassMetadata;

class Message {
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
      'type' => 'clob'
    ));
    $metadata->mapField(array(
      'fieldName' => 'headers',
      'type' => 'object'
    ));
  }

}