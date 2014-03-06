<?php
/*
 * This file is part of the Phonarc package.
 *
 * (c) Greg Payne
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phonarc\Console;

class Application extends \Symfony\Component\Console\Application {
  public function __construct() {
    parent::__construct();

    // Identify all of the available phonarc console commands.
    $cmds = array(
      new CommandInstall(),
      new CommandDownload(),
      new Manager(),
    );

    // Add the commands after eliminating the implicit phonarc namespace.
    foreach ($cmds as &$cmd) {
      $cmd->setName(str_replace('phonarc:', '', $cmd->getName()));
      $this->add($cmd);
    }
  }
}