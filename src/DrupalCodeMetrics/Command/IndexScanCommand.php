<?php

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Scan the completion status of known projects, and run their queued tasks.
 */
class IndexScanCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {

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
        NULL,
        InputOption::VALUE_NONE,
        'Drops the database index completely before running.'
      )
      ->addOption(
        'flush',
        NULL,
        InputOption::VALUE_NONE,
        'Will overwrite and re-index any module previously found.'
      );
  }

  /**
   * @inheritdoc
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

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
