<?php
namespace Phonarc\Message\Helper;

use Phonarc\Context\PhonarcContext;
use Symfony\Component\Console\Output\OutputInterface;
use Phonarc\Message\Message;

abstract class MessageSync {
  abstract public function process(Message &$message, PhonarcContext $context, OutputInterface $output);

}