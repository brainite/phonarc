<?php
/*
 * This file is part of the FileConverter package.
 *
 * (c) Greg Payne
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Phonarc\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Phonarc\Context\PhonarcContext;
use Symfony\Component\Console\Input\InputOption;



class CommandInstall extends \Symfony\Component\Console\Command\Command {
  protected function configure() {
    $this->setName('phonarc:install');
    $this->setDescription('Install Phonarc');
    $this->setDefinition(array(
      new InputOption('conf', NULL, InputOption::VALUE_REQUIRED, 'Specify a phonarc configuration file', './phonarc.yml'),
      new InputOption('update', NULL, InputOption::VALUE_NONE, 'Perform Doctrine updates'),
    ));
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    // Add Doctrine subcommands.
    $app = $this->getApplication();
    $app->add(new \Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand);
    $app->add(new \Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand);

    // Tests to run.
    $tests = array(
      array(
        'title' => 'Update from composer.',
        'test' => class_exists('\Symfony\Component\Console\Command\Command'),
        'instructions' => 'cd ' . escapeshellarg(dirname(dirname(__DIR__)))
          . '; composer.phar update',
      ),
      array(
        'title' => 'Install getmail.',
        'test' => array(
          'which',
          'getmail'
        ),
        'instructions' => 'sudo apt-get install getmail4',
      ),
      array(
        'title' => 'Install mhonarc.',
        'test' => array(
          'which',
          'mhonarc'
        ),
        'instructions' => 'sudo apt-get install mhonarc',
      ),
    );

    // Run the tests.
    $fail = 0;
    foreach ($tests as &$test) {
      $test['status'] = '';
      $test['pass'] = TRUE;
      if (is_bool($test['test'])) {
        if (!$test['test']) {
          $test['pass'] = FALSE;
          $fail++;
        }
      }
      elseif ($test['test'][0] === 'which') {
        $path = trim(shell_exec('which ' . escapeshellarg($test['test'][1])));
        if ($path === '') {
          $test['pass'] = FALSE;
          $fail++;
        }
        else {
          $test['status'] .= 'Found: ' . $path;
        }
      }
      else {
        $test['pass'] = FALSE;
        $fail++;
      }
    }
    unset($test);

    // Output the summary.
    $output->writeln("Phonarc installation:");
    foreach ($tests as $test) {
      if ($test['pass']) {
        $output->writeln("  [DONE]    " . $test['title']);
      }
      else {
        $output->writeln("  [PENDING] " . $test['title']);
        $output->writeln("            " . $test['instructions']);
      }
      if ($test['status'] !== '') {
        $output->writeln("            " . $test['status']);
      }
    }

    if ($fail) {
      $output->writeln("Correct these issues and run again.");
      return;
    }

    // Load the configuration file and install the schema for each list.
    if ($output->isVerbose()) {
      $output->writeln("Doctrine ORM installation:");
    }

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

      // Build the command object and resources.
      $helpers = $context->getHelperSet();
      $command = $app->find('orm:schema-tool:create');
      $command->setHelperSet($helpers);
      $arguments = array(
        'command' => 'orm:schema-tool:create',
      );
      $input2 = new ArrayInput($arguments);

      // Run the command to create the resources.
      try {
        $returnCode = $command->run($input2, $output);
        if ($returnCode == 0) {
          $output->writeln("  [DONE]    " . "Table created.");
        }
      } catch (\Doctrine\ORM\Tools\ToolsException $e) {
        if (preg_match("@Table '[^']+' already exists@", $e->getMessage())) {
          $output->writeln("  [DONE]    " . "Table already exists.");
          if ($input->getOption('update')) {
            $command = $app->find('orm:schema-tool:update');
            $command->setHelperSet($helpers);
            $arguments = array(
              'command' => 'orm:schema-tool:update',
              '--force' => TRUE,
            );
            $input2 = new ArrayInput($arguments);
            $returnCode = $command->run($input2, $output);
            if ($returnCode == 0) {
              $output->writeln("  [DONE]    " . "Table updated.");
            }
          }
        }
        else {
          throw $e;
        }
      }
    }

    if ($fail == 0) {
      $output->writeln("Success! Phonarc appears to be fully installed.");
    }
  }

}