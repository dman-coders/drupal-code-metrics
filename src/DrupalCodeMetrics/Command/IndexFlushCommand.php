<?php

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Resets the qued task status of all items, to allow re-queuing.
 */
class IndexFlushCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('index:flush')
      ->setDescription('Clears the current reports of the indexed items. Resets their tasks to re-queue them for processing.')
      ->addOption(
        'drop',
        NULL,
        InputOption::VALUE_NONE,
        'Drop the database completely and reset from scratch'
      )
    ;
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
    $index->setOutput($output);

    if ($input->getOption('drop')) {
      $index->rebuild();
      $output->writeln('Emptied database.');
    }
    else {
      $index->resetAllStatus();
      $output->writeln('Reset the status of all items.');
    }
  }

}
