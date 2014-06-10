<?php
namespace Phonarc\Message\Helper;

use Phonarc\Context\PhonarcContext;
class MessageQuery {
  static public function getIdFromMhonarcMessage($mhonarc_message) {
    $em = PhonarcContext::factory()->getEntityManager();
    $qb = $em->createQueryBuilder();
    $qb->select('m.id');
    $qb->from('Phonarc\Message\Message', 'm');
    $qb->where('m.mhonarc_message = ?1');
    $qb->setParameter(1, $mhonarc_message);
    try {
      return (int) $qb->getQuery()->getSingleResult();
    } catch (\Exception $e) {
      return 0;
    }
  }

}