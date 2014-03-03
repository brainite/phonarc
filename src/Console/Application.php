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

class Application extends \Symfony\Component\Console\Application {
  public function __construct() {
    parent::__construct();

    // Identify all of the available phonarc console commands.
    $cmds = array(
      new Install(),
      new CommandDownload(),
      new Mhonarc(),
      new PdoImport(),
      new Manager(),
    );

    // Add the commands after eliminating the implicit phonarc namespace.
    foreach ($cmds as &$cmd) {
      $cmd->setName(str_replace('phonarc:', '', $cmd->getName()));
      $this->add($cmd);
    }
  }
}