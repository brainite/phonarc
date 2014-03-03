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
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\OutputInterface;

class Getmail extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this->setName('phonarc:getmail');
    $this->setDescription('1. Download email');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $conf_path = 'lists.yml';
    $confs = Yaml::parse($conf_path);

    foreach ($confs as $conf_id => $conf) {
      $root = realpath($conf['path']);
      if (!$root || !is_dir($root)) {
        $output->writeln("Invalid local directory: " . $conf['path']);
        continue;
      }

      // Initialize the mbox.
      $mbox_path = tempnam($root, strftime('.email.%Y%m%d%H%M%S.'));
      touch($mbox_path);
      chmod($mbox_path, 0777);

      // Apply default settings to the configuration.
      $getmail_ini_path = tempnam($root, '.getmailconf.');
      $conf['getmail'] = array_replace_recursive(array(
        'options' => array(
          'verbose' => 0,
          'delete' => 0,
          'max_messages_per_session' => 1,
        ),
        'retriever' => array(
          'type' => 'BrokenUIDLPOP3Retriever',
          'server' => 'localhost',
          'username' => 'unknown',
          'password' => 'password',
        ),
        'destination' => array(
          'type' => 'Mboxrd',
          'user' => 'apache',
          "path" => $mbox_path,
        ),
      ), $conf['getmail']);

      // Build an ini file for getmail.
      $data = '';
      foreach ($conf['getmail'] as $section_id => $params) {
        $data .= "[$section_id]\n";
        foreach ($params as $k => $v) {
          if (is_string($v)) {
            if ($v === '') {
              $v = 0;
            }
          }
          elseif (is_bool($v)) {
            $v = $v ? '1' : '0';
          }
          $data .= $k . ' = ' . $v . "\n";
        }
      }
      file_put_contents($getmail_ini_path, $data);

      // Execute getmail
      $getmail_output = shell_exec("getmail -r "
        . escapeshellarg($getmail_ini_path));

      // Cleanup
      unlink($getmail_ini_path);
      if (filesize($mbox_path) == 0) {
        unlink($mbox_path);
      }
    }
  }

}