<?php

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Scan folders for projects and enumerate the 'Module' projects found.
 */
class IndexListCommand extends Command {

  /**
   * @inheritdoc
   */
  protected function configure() {

    $this
      ->setName('index:list')
      ->setDescription('Recurse a folder to enumerate the modules in it. This just counts and queues the found module projects.')
      ->addArgument(
        'path',
        InputArgument::IS_ARRAY | InputArgument::REQUIRED,
        'Filepath to scan'
      )
      ->addOption(
        'process',
        NULL,
        InputOption::VALUE_NONE,
        'Also start the process of running the scans on it. This takes longer than just listing them.'
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

    $paths = $input->getArgument('path');

    if ($input->getOption('process')) {
      $output->writeln("Will scan and process things we find there.");
    }

    // Initialize the index, which is both the worker
    // and the interface to the database.
    $options = $this->getApplication()->options;
    $index = new Index($options);
    // Tell the index where to log to.
    // This also allows it access to the verbosity option.
    $index->setoutput($output);

    foreach ($paths as $path) {
      $index->indexFolder($path);
    }
  }

}
