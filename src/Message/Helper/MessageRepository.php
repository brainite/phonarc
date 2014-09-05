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
class MessageRepository {
  static public function findById($message_id) {
    return MessageRepository::findOneMessageBy(array(
      'id' => $message_id,
    ));
  }

  static public function findOneMessageBy($conditions) {
    $em = PhonarcContext::factory()->getEntityManager();
    $repo = $em->getRepository('Phonarc\Message\Message');
    return $repo->findOneBy($conditions);
  }

}