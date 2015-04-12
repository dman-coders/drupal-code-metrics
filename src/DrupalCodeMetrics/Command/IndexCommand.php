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

class IndexCommand extends Command {

  protected function configure()
  {
    $this
      ->setName('index:list')
      ->setDescription('Recurse a folder to enumerate the modules in it.')
      ->addArgument(
        'path',
        InputArgument::IS_ARRAY | InputArgument::REQUIRED,
        'Filepath to scan'
      )
      ->addOption(
        'process',
        null,
        InputOption::VALUE_NONE,
        'Also start the process of running the scans on it. This takes longer than just listing them.'
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
    $paths = $input->getArgument('path');

    if ($input->getOption('process')) {
      $output->writeln("Will scan and process things we find there.");
    }

    // Initialize the index, which is both the worker
    // and the interface to the database.
    $options = $this->getApplication()->options;
    $index = new Index($options);

    foreach ($paths as $path) {
      $index->indexFolder($path);
    }
  }

}
