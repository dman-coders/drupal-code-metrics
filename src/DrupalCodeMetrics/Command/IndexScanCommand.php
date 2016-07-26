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
use Symfony\Component\Console\Helper\ProgressBar;

class IndexScanCommand extends Command {

  protected function configure()
  {
    $this
      ->setName('index:scan')
      ->setDescription('Progressively runs all available tests on the queued/indexed items.')
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
    // Tell the index where to log to.
    // This also allows it access to the verbosity option.
    $index->setoutput($output);

    if ($max_tasks = $input->getOption('max-tasks')) {
      $index->setOption('max-tasks', $max_tasks);
    }
    $max_tasks = $index->getOption('max-tasks');

    // Add a progress bar, why not?
    $progress = new ProgressBar($output, $max_tasks);
    $index->setProgress($progress);
    $progress->start();

    $index->runTasks();

    $progress->finish();
    $output->writeln('');

  }

}
