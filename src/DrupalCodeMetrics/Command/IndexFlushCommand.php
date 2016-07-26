<?php
/**
 * @file
 * Commandline processing. Interface to the Index to trigger tasks and reports.
 */

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexFlushCommand extends Command {

  protected function configure()
  {
    $this
        ->setName('index:flush')
        ->setDescription('Clears the current reports of the indexed items. Resets their tasks to re-queue them for processing.')
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

    $index->resetAllStatus();
    $output->writeln('Reset the status of all items.');

  }

}
