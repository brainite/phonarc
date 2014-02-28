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

class Install extends \Symfony\Component\Console\Command\Command{
  protected function configure() {
    $this->setName('phonarc:install');
    $this->setDescription('Install Phonarc');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $tests = array(
      array(
        'title' => 'Update from composer.',
        'test' => array(
          'class_exists',
          '\Symfony\Component\Console\Command\Command'
        ),
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
      if ($test['test'][0] === 'which') {
        $path = trim(shell_exec('which ' . escapeshellarg($test['test'][1])));
        if ($path === '') {
          $test['pass'] = FALSE;
          $fail++;
        }
        else {
          $test['status'] .= 'Found: ' . $path;
        }
      }
      elseif (function_exists($test['test'][0])) {
        $func = array_shift($test['test']);
        $tmp = call_user_func_array($func, $test['test']);
        if (!$tmp) {
          $test['pass'] = FALSE;
          $fail++;
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
    if ($fail == 0) {
      $output->writeln("Success! Phonarc appears to be fully installed.");
    }
  }

}