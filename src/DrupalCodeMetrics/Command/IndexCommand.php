<?php
/**
 * @file
 * Commandline processing. Interface to the Index to trigger tasks and reports.
 *
 * My first attempt at a Symfony Console.
 * http://symfony.com/doc/current/components/console/introduction.html
 */

namespace DrupalCodeMetrics\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class IndexCommand extends Command {


  protected function configure()
  {
    $this
      ->setName('index:scan')
      ->setDescription('Recurse a folder to enumerate the modules in it.')
      ->addArgument(
        'path',
        InputArgument::REQUIRED,
        'Filepath to scan'
      )
      ->addOption(
        'process',
        null,
        InputOption::VALUE_NONE,
        'Also start the process of running the scans on it. This takes longer than just listing them.'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $path = $input->getArgument('path');
    $output->writeln("About to index the contents of $path");

    if ($input->getOption('process')) {
      $output->writeln("Will scan and process things we find there.");
    }

  }

}
