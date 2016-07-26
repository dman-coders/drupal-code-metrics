<?php

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Initializes the database. Empties it if it exists.
 */
class InitializeCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {
    $this
      ->setName('init')
      ->setDescription('Initializes the database index. Empties and rebuilds it if it exists.')
      ->setHelp('This utility uses a local database to keep track of what has and has nott been indexed. By default this will be a sqlite db in the installation directory. If you prefer, you can use othe database layers by editing the Database configuration parameters in bootstrap.php.')
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

    $index->rebuild();
  }

}
