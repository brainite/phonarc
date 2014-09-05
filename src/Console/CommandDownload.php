<?php
/*
 * This file is part of the Phonarc package.
 *
 * (c) CPNP
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phonarc\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Phonarc\Context\PhonarcContext;
use Phonarc\Message\Message;

class CommandDownload extends \Symfony\Component\Console\Command\Command {
  const RUN_LIMIT = 20;

  protected function configure() {
    $this->setName('phonarc:download');
    $this->setDescription('Download email and import into MHonArc');
    $this->setDefinition(array(
      new InputOption('conf', NULL, InputOption::VALUE_REQUIRED, 'Specify a phonarc configuration file', './phonarc.yml'),
    ));
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $output->getFormatter()->setStyle('h1', new OutputFormatterStyle('black', 'yellow', array(
      'bold',
    )));

    // Locate the configuration.
    $conf_path = $input->getOption('conf');
    try {
      PhonarcContext::loadConf($conf_path);
    } catch (\Exception $e) {
      $output->writeln("<error>You must specify a valid configuration file via --conf.</error>");
      return;
    }

    while (TRUE) {
      // Break the loop
      $context = PhonarcContext::factory(PhonarcContext::NEXT_CONTEXT);
      if (!isset($context)) {
        break;
      }

      // Extract key variables.
      $conf_id = $context->getId();
      $conf = $context->getConf();

      // Locate the key directory.
      if ($output->isVerbose()) {
        $output->writeln("<h1>LOADING CONFIGURATION: " . $conf_id . "</h1>");
      }
      $root = $conf['basepath'];
      if (!$root || (!is_dir($root) && !mkdir($root))) {
        $output->writeln("Invalid local directory: " . $conf['basepath']);
        continue;
      }
      $root = realpath($root);

      $count = 0;
      while (!$count || $count < $conf['max_downloads']) {
        // Initialize the mbox.
        $count++;
        if ($output->isVerbose()) {
          $output->writeln("Initialize mbox #$count.");
        }
        $mbox_path = tempnam($root, strftime('.email.%Y%m%d%H%M%S.'));
        touch($mbox_path);
        chmod($mbox_path, 0777);

        // Build an ini file for getmail.
        $conf['getmail']['destination']['path'] = $mbox_path;
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
          // If nothing was downloaded, then stop.
          break;
        }

        // Eliminate any non-header lines from the beginning of the file.
        $dat = file_get_contents($mbox_path);
        if (preg_match("@^[^:\r\n]+[\r\n]+@s", $dat, $arr)) {
          $dat = substr($dat, strlen($arr[0]));
          file_put_contents($mbox_path, $dat);
        }
      }


      // Import the file into MHonArc
      if ($output->isVerbose()) {
        $output->writeln("MHonArc: processing configuration.");
      }
      $conf['mhonarc']['outdir'] = $root;

      // Prep the mhonarc template.
      $tpl_path = $root . '/.mhonarc.rss.tpl';
      $conf['mhonarc']['rcfile'] = $tpl_path;
      file_put_contents($tpl_path, $this->getMHonArcRcTpl($conf));

      // Locate the emails to process.
      $emails = glob("$root/.email*", GLOB_NOSORT);
      if (empty($emails)) {
        if ($output->isVerbose()) {
          $output->writeln("MHonArc: no emails to process.");
        }
        goto end_getmail_to_mhonarc;
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
      @unlink($tpl_path);

      end_getmail_to_mhonarc:

      // Import the emails into doctrine.
      $this->mhonarcToDoctrine($context, $output);

      // Sync the emails to the secondary data source.
      $this->doctrineToOther($context, $output);
    }
  }

  private function doctrineToOther(PhonarcContext $context, OutputInterface $output) {
    $limit = self::RUN_LIMIT;

    // Create the sync engine.
    $sync_conf = $context->getConf('sync');
    $class = $sync_conf['class'];
    if (!isset($class) || !class_exists($class)) {
      $output->writeln("No sync class configured or available.");
      return;
    }
    $sync_engine = new $class();

    // Load the EntityManager
    $em = $context->getEntityManager();

    // Auto-correct any messages imported via an inactive configuration.
    $qb = $em->createQueryBuilder();
    $qb->select('m.id');
    $qb->from('Phonarc\Message\Message', 'm');
    $qb->where('m.sync_context_version != ?1');
    $qb->setParameter(1, $context->getConf('message.version'));
    $qb->orderBy('m.mhonarc_message', 'ASC');
    $regenerate = $qb->getQuery()->getArrayResult();
    if (!empty($regenerate)) {
      foreach ($regenerate as $old) {
        if (--$limit < 0) {
          break;
        }
        try {
          $msg = $em->find('Phonarc\Message\Message', $old['id']);
          $em->persist($msg);
          $sync_engine->process($msg, $context, $output);
          $msg->setSyncContextVersion($context->getConf('message.version'));
        } catch (\Exception $e) {
          $output->writeln("<error>Error syncing message id:" . $old['id']
              . "</error>");
          $output->writeln("<error>" . $e->getMessage() . "</error>");
        }
      }
      $em->flush();
    }
  }

  private function getMHonArcRcTpl($conf) {
    // Use the string loader to avoid cache creation and pollution of filesystem.
    $tpl = file_get_contents(__DIR__ . '/Resources/mhonarc_rc.tpl.twig');
    $loader = new \Twig_Loader_String();
    $twig = new \Twig_Environment($loader);
    return $twig->render($tpl, $conf);
  }

  private function mhonarcToDoctrine(PhonarcContext $context, OutputInterface $output) {
    $limit = self::RUN_LIMIT;

    // Load the EntityManager
    $em = $context->getEntityManager();

    // Auto-correct any messages imported via an inactive configuration.
    $qb = $em->createQueryBuilder();
    $qb->select('m.id');
    $qb->from('Phonarc\Message\Message', 'm');
    $qb->where('m.context_version != ?1');
    $qb->setParameter(1, $context->getConf('message.version'));
    $regenerate = $qb->getQuery()->getArrayResult();
    if (!empty($regenerate)) {
      foreach ($regenerate as $old) {
        if (--$limit < 0) {
          break;
        }
        try {
          $msg = $em->find('Phonarc\Message\Message', $old['id']);
          $em->persist($msg);
          $msg->updateFromMhonarc();
        } catch (\Exception $e) {
          $output->writeln("<error>Error updating message id:" . $old['id']
            . "</error>");
          $output->writeln("<error>" . $e->getMessage() . "</error>");
        }
      }
      $em->flush();
    }

    // Get a list of messages that have been loaded via the current version.
    $qb = $em->createQueryBuilder();
    $qb->select('m.mhonarc_message');
    $qb->from('Phonarc\Message\Message', 'm');
    $qb->where('m.context_version = ?1');
    $qb->setParameter(1, $context->getConf('message.version'));
    $complete_ids = $qb->getQuery()->getArrayResult();
    foreach ($complete_ids as $k => $v) {
      $complete_ids[$k] = array_pop($v);
    }

    // Iterate over the msg files in the local directory.
    $mhonarc_messages = new \RegexIterator(new \FilesystemIterator($context->getConf('basepath')), '@/(msg\d+\.html)$@', \RegexIterator::MATCH);
    foreach ($mhonarc_messages as $fileinfo) {
      $basename = basename($fileinfo->getFileName());

      // Skip any messages that are already in the current version.
      if (in_array($basename, $complete_ids)) {
        continue;
      }

      //     $msg = $em->getRepository('Phonarc\Message\Message')->findOneBy(array(
      //       'mhonarc_message' => $msg_basename,
      //     ));

      // Create a new message and update it from the file.
      if (--$limit < 0) {
        break;
      }
      $msg = new Message();
      $em->persist($msg);
      $msg->setMhonarcMessage($basename);
      $msg->updateFromMhonarc();
    }
    $em->flush();
  }

}