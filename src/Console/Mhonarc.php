<?php
/*
 * This file is part of the Witti FileConverter package.
 *
 * (c) Greg Payne
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Witti\Phonarc\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Mhonarc extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this->setName('phonarc:mhonarc');
    $this->setDescription('2. Convert email to HTML archive');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
  }

}