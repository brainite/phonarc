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
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\OutputInterface;

class CommandDownload extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this->setName('phonarc:download');
    $this->setDescription('1. Download email and import into MHonArc');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->getFormatter()->setStyle('h1', new OutputFormatterStyle('black', 'yellow', array(
      'bold',
    )));

    $conf_path = 'lists.yml';
    $confs = Yaml::parse($conf_path);

    foreach ($confs as $conf_id => $conf) {
      if ($output->isVerbose()) {
        $output->writeln("<h1>LOADING CONFIGURATION: " . $conf_id . "</h1>");
      }
      $root = realpath($conf['basepath']);
      if (!$root || !is_dir($root)) {
        $output->writeln("Invalid local directory: " . $conf['basepath']);
        continue;
      }

      // Initialize the mbox.
      if ($output->isVerbose()) {
        $output->writeln("Initialize the mbox.");
      }
      $mbox_path = tempnam($root, strftime('.email.%Y%m%d%H%M%S.'));
      touch($mbox_path);
      chmod($mbox_path, 0777);

      // Apply default settings to the configuration.
      $conf = array_replace_recursive(array(
        'getmail' => array(
          'options' => array(
            'verbose' => 0,
            'delete' => 0,
          ),
          'retriever' => array(
            'type' => 'BrokenUIDLPOP3Retriever',
            'server' => 'localhost',
            'username' => 'unknown',
            'password' => 'password',
          ),
        ),
        'mhonarc' => array(
          'idxsize' => 2000,
          'idxfname' => 'archive.rss',
        ),
      ), $conf, array(
        'getmail' => array(
          'options' => array(
            'max_messages_per_session' => 1,
          ),
          'destination' => array(
            'type' => 'Mboxrd',
            'user' => 'apache',
            "path" => $mbox_path,
          ),
        ),
      ));

      // Build an ini file for getmail.
      if ($output->isVerbose()) {
        $output->writeln("Getmail: writing configuration");
      }
      $getmail_ini_path = tempnam($root, '.getmailconf.');
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
      if ($output->isVerbose()) {
        $output->writeln("Getmail: running.");
      }
      $getmail_output = shell_exec("getmail -r "
        . escapeshellarg($getmail_ini_path));

      // Getmail cleanup
      if ($output->isVerbose()) {
        $output->writeln("Getmail: cleanup.");
      }
      unlink($getmail_ini_path);
      if (filesize($mbox_path) == 0) {
        unlink($mbox_path);
      }

      // Import the file into MHonArc
      if ($output->isVerbose()) {
        $output->writeln("MHonArc: processing configuration.");
      }
      $conf['mhonarc']['outdir'] = $root;

      // Prep the mhonarc template.
      $tpl_path = $root . '/.mhonarc.rss.tpl';
      $conf['mhonarc']['rcfile'] = $tpl_path;
      file_put_contents($tpl_path, $this->getMHonArcRssTpl($conf));

      // Locate the emails to process.
      $emails = glob("$root/.email*", GLOB_NOSORT);
      if (empty($emails)) {
        if ($output->isVerbose()) {
          $output->writeln("MHonArc: no emails to process.");
        }
        continue;
      }
      else {
        if ($output->isVerbose()) {
          $output->writeln("MHonArc: located " . sizeof($emails) . " emails.");
        }
        sort($emails);
      }

      // Run mhonarc.
      foreach ($emails as $email) {
        // Other params: -maxsize 100 -expireage 5184000
        $cmd = 'mhonarc -add -treverse -reverse -idxprefix rss -multipg ';
        foreach ($conf['mhonarc'] as $k => $v) {
          $cmd .= ' ' . escapeshellarg('-' . $k) . ' ' . escapeshellarg($v);
        }
        $cmd .= ' ' . escapeshellarg($email);

        // Suppress mhonarc errors: defined(%hash) is deprecated
        $cmd .= " 2>&1 | egrep -v 'defined\(' ";

        if ($output->isVeryVerbose()) {
          $output->writeln('<info>' . $cmd . '</info>');
        }
        $mhonarc_output = shell_exec($cmd);
        if (preg_match('@1 new message@', $mhonarc_output)) {
          if ($output->isVerbose()) {
            $output->writeln("MHonArc: email imported.");
          }
          unlink($email);
        }
        elseif (preg_match('@No new message@', $mhonarc_output)) {
          if ($output->isVerbose()) {
            $output->writeln("MHonArc: email was already imported.");
          }
          unlink($email);
        }
        else {
          if ($output->isVerbose()) {
            $output->writeln("MHonArc: warning - email not imported?");
          }
        }
        if ($output->isVeryVerbose()) {
          $output->writeln($mhonarc_output);
        }
      }
    }
  }

  private function getMHonArcRssTpl($conf) {
    // Use the string loader to avoid cache creation and pollution of filesystem.
    $tpl = file_get_contents(__DIR__ . '/Resources/rss.tpl.twig');
    $loader = new \Twig_Loader_String();
    $twig = new \Twig_Environment($loader);
    return $twig->render($tpl, $conf);
  }

}