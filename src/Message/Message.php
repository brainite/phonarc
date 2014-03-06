<?php
namespace Witti\Phonarc\Message;
use Doctrine\ORM\Mapping\ClassMetadata;
use Witti\Phonarc\Context\PhonarcContext;
use Doctrine\ORM\Mapping as ORM;

class Message {
  public static function loadMetadata(ClassMetadata $metadata) {
    // Get the current context.
    $context = PhonarcContext::factory(PhonarcContext::CURRENT_CONTEXT);
    if (!isset($context)) {
      throw new \ErrorException("Phonarc requires a valid context prior to loading metadata.");
    }

    // Determine the prefix for the table.
    $prefix = $context->getConf('doctrine.prefix');

    // Build the metadata.
    $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
    $metadata->mapField(array(
      'id' => TRUE,
      'fieldName' => 'id',
      'type' => 'integer',
      'generator' => TRUE,
    ));
    $metadata->mapField(array(
      'fieldName' => 'date_sent',
      'type' => 'datetime',
    ));
    $metadata->mapField(array(
      'fieldName' => 'mhonarc_thread',
      'length' => 32,
    ));
    $metadata->mapField(array(
      'fieldName' => 'mhonarc_message',
      'length' => 16,
    ));
    $metadata->mapField(array(
      'fieldName' => 'context_version',
      'length' => 16,
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
      'type' => 'integer',
    ));
    $metadata->mapField(array(
      'fieldName' => 'parent_id',
      'type' => 'integer',
    ));
    $metadata->mapField(array(
      'fieldName' => 'body',
      'type' => 'text',
    ));
    $metadata->mapField(array(
      'fieldName' => 'headers',
      'type' => 'json_array',
    ));
    $metadata->mapField(array(
      'fieldName' => 'sync_thread_id',
      'type' => 'integer',
    ));
    $metadata->mapField(array(
      'fieldName' => 'sync_message_id',
      'type' => 'integer',
    ));
    $metadata->mapField(array(
      'fieldName' => 'sync_context_version',
      'length' => 16,
    ));
    $metadata->setPrimaryTable(array(
      'name' => $prefix . 'message',
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
        'mhonarc_thread' => array(
          'columns' => array(
            'mhonarc_thread',
          ),
        ),
        'context_version' => array(
          'columns' => array(
            'context_version',
          ),
        ),
        'sync_context_version' => array(
          'columns' => array(
            'sync_context_version',
          ),
        ),
      ),
      'uniqueConstraints' => array(
        'mhonarc_message' => array(
          'columns' => array(
            'mhonarc_message',
          ),
        ),
      ),
    ));
  }

  public function updateFromMhonarc() {
    // Get the context.
    $context = PhonarcContext::factory(PhonarcContext::CURRENT_CONTEXT);
    Helper\MessageInit::process($context, $this);
  }

  /**
   * @var integer
   */
  private $id;

  /**
   * @var \DateTime
   */
  private $date_sent;

  /**
   * @var string
   */
  private $mhonarc_thread;

  /**
   * @var string
   */
  private $mhonarc_message;

  /**
   * @var string
   */
  private $subject;

  /**
   * @var string
   */
  private $from_email;

  /**
   * @var string
   */
  private $from_name;

  /**
   * @var integer
   */
  private $thread_id;

  /**
   * @var integer
   */
  private $parent_id;

  /**
   * @var integer
   */
  private $sync_message_id;

  /**
   * @var integer
   */
  private $sync_thread_id;

  /**
   * @var string
   */
  private $body;

  /**
   * @var string
   */
  private $context_version;

  /**
   * @var string
   */
  private $sync_context_version;

  /**
   * @var \stdClass
   */
  private $headers;

  /**
   * Set id
   *
   * @param integer $id
   * @return Message
   */
  public function setId($id) {
    $this->id = $id;

    return $this;
  }

  /**
   * Get id
   *
   * @return integer
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Set date_sent
   *
   * @param \DateTime $dateSent
   * @return Message
   */
  public function setDateSent($dateSent) {
    $this->date_sent = $dateSent;

    return $this;
  }

  /**
   * Get date_sent
   *
   * @return \DateTime
   */
  public function getDateSent() {
    return $this->date_sent;
  }

  /**
   * Set mhonarc_thread
   *
   * @param string $mhonarcThread
   * @return Message
   */
  public function setMhonarcThread($mhonarcThread) {
    $this->mhonarc_thread = $mhonarcThread;

    return $this;
  }

  /**
   * Get mhonarc_thread
   *
   * @return string
   */
  public function getMhonarcThread() {
    return $this->mhonarc_thread;
  }

  /**
   * Set mhonarc_message
   *
   * @param string $mhonarcMessage
   * @return Message
   */
  public function setMhonarcMessage($mhonarcMessage) {
    $this->mhonarc_message = $mhonarcMessage;

    return $this;
  }

  /**
   * Get mhonarc_message
   *
   * @return string
   */
  public function getMhonarcMessage() {
    return $this->mhonarc_message;
  }

  /**
   * Set subject
   *
   * @param string $subject
   * @return Message
   */
  public function setSubject($subject) {
    $this->subject = $subject;

    return $this;
  }

  /**
   * Get subject
   *
   * @return string
   */
  public function getSubject() {
    return $this->subject;
  }

  /**
   * Set from_email
   *
   * @param string $fromEmail
   * @return Message
   */
  public function setFromEmail($fromEmail) {
    $this->from_email = $fromEmail;

    return $this;
  }

  /**
   * Get from_email
   *
   * @return string
   */
  public function getFromEmail() {
    return $this->from_email;
  }

  /**
   * Set from_name
   *
   * @param string $fromName
   * @return Message
   */
  public function setFromName($fromName) {
    $this->from_name = $fromName;

    return $this;
  }

  /**
   * Get from_name
   *
   * @return string
   */
  public function getFromName() {
    return $this->from_name;
  }

  /**
   * Set thread_id
   *
   * @param integer $threadId
   * @return Message
   */
  public function setThreadId($threadId) {
    $this->thread_id = $threadId;

    return $this;
  }

  /**
   * Get thread_id
   *
   * @return integer
   */
  public function getThreadId() {
    return $this->thread_id;
  }

  /**
   * Set parent_id
   *
   * @param integer $parentId
   * @return Message
   */
  public function setParentId($parentId) {
    $this->parent_id = $parentId;

    return $this;
  }

  /**
   * Get parent_id
   *
   * @return integer
   */
  public function getParentId() {
    return $this->parent_id;
  }

  /**
   * Set context_version
   *
   * @param string $context_version
   * @return Message
   */
  public function setContextVersion($context_version) {
    $this->context_version = $context_version;

    return $this;
  }

  /**
   * Get context_version
   *
   * @return string
   */
  public function getContextVersion() {
    return $this->context_version;
  }

  /**
   * Set sync_context_version
   *
   * @param string $sync_context_version
   * @return Message
   */
  public function setSyncContextVersion($sync_context_version) {
    $this->sync_context_version = $sync_context_version;

    return $this;
  }

  /**
   * Get sync_context_version
   *
   * @return string
   */
  public function getSyncContextVersion() {
    return $this->sync_context_version;
  }

  /**
   * Set sync_message_id
   *
   * @param string $sync_message_id
   * @return Message
   */
  public function setSyncMessageId($sync_message_id) {
    $this->sync_message_id = $sync_message_id;

    return $this;
  }

  /**
   * Get sync_message_id
   *
   * @return string
   */
  public function getSyncMessageId() {
    return $this->sync_message_id;
  }

  /**
   * Set sync_thread_id
   *
   * @param string $sync_thread_id
   * @return Message
   */
  public function setSyncThreadId($sync_thread_id) {
    $this->sync_thread_id = $sync_thread_id;

    return $this;
  }

  /**
   * Get sync_thread_id
   *
   * @return string
   */
  public function getSyncThreadId() {
    return $this->sync_thread_id;
  }

  /**
   * Set body
   *
   * @param string $body
   * @return Message
   */
  public function setBody($body) {
    $this->body = $body;

    return $this;
  }

  /**
   * Get body
   *
   * @return string
   */
  public function getBody() {
    return $this->body;
  }

  /**
   * Set headers
   *
   * @param \stdClass $headers
   * @return Message
   */
  public function setHeaders($headers) {
    $this->headers = $headers;

    return $this;
  }

  /**
   * Get headers
   *
   * @return \stdClass
   */
  public function getHeaders() {
    return $this->headers;
  }

}