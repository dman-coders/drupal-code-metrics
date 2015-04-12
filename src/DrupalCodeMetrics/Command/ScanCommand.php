<?php
/**
 * @file
 * Commandline processing. Interface to the Index to trigger tasks and reports.
 *
 * My first attempt at a Symfony Console.
 * http://symfony.com/doc/current/components/console/introduction.html
 */

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ScanCommand extends Command {

  protected function configure()
  {
    $this
      ->setName('index:scan')
      ->setDescription('Recurse a folder to enumerate the modules in it.')
      ->addOption(
        'max-tasks',
        10,
        InputOption::VALUE_OPTIONAL,
        'Number of tasks to process.'
      )
      ->addOption(
        'reset',
        null,
        InputOption::VALUE_NONE,
        'Drops the database index completely before running.'
      )
      ->addOption(
        'flush',
        null,
        InputOption::VALUE_NONE,
        'Will overwrite and re-index any module previously found.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    // Initialize the index, which is both the worker
    // and the interface to the database.
    $options = $this->getApplication()->options;
    $index = new Index($options);

    if ($max_tasks = $input->getOption('max-tasks')) {
      $index->setOption('max-tasks', $max_tasks);
    }

    $index->runTasks();

  }

}
