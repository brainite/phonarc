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
class MessageQuery {
  static public function getIdFromMhonarcMessage($mhonarc_message) {
    $em = PhonarcContext::factory()->getEntityManager();
    $qb = $em->createQueryBuilder();
    $qb->select('m.id');
    $qb->from('Phonarc\Message\Message', 'm');
    $qb->where('m.mhonarc_message = ?1');
    $qb->setParameter(1, $mhonarc_message);
    try {
      $result = $qb->getQuery()->getSingleScalarResult();
      return (int) $result;
    } catch (\Exception $e) {
      return 0;
    }
  }

}