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
use Symfony\Component\Console\Output\OutputInterface;
use Phonarc\Message\Message;

abstract class MessageSync {
  abstract public function process(Message &$message, PhonarcContext $context, OutputInterface $output);

}