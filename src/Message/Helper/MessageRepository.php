<?php
namespace Phonarc\Message\Helper;

use Phonarc\Context\PhonarcContext;
class MessageRepository {
  static public function findById($message_id) {
    return MessageRepository::findOneMessageBy(array(
      'mhonarc_message' => $message_id,
    ));
  }

  static public function findOneMessageBy($conditions) {
    $em = PhonarcContext::factory()->getEntityManager();
    $repo = $em->getRepository('Phonarc\Message\Message');
    return $repo->findOneBy($conditions);
  }

}