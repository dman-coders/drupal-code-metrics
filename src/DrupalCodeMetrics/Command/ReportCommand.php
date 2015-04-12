<?php
/**
 * @file
 * Commandline processing. Interface to the Index to trigger tasks and reports.
 *
 * My first attempt at a Symfony Console.
 * http://symfony.com/doc/current/components/console/introduction.html
 *
 * This file is pulled in as needed by the Application.
 */

namespace DrupalCodeMetrics\Command;

use DrupalCodeMetrics\Index;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ReportCommand extends Command {


  protected function configure() {
    $this
      ->setName('report:dump')
      ->setDescription('Dump the state of the tables')
      ->setHelp('Direct access to the sqlite database that contains a list of all known modules and their versions. This database tracks the auditing progress, and is persistent between invocations.')
      ->addOption(
        'format',
        NULL,
        InputOption::VALUE_OPTIONAL,
        'Format for return data. values may be [json,cst,tsxt]'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    if ($input->getOption('format')) {
    }

    $output->writeln("Dump the contents of the tables");

    $options = $this->getApplication()->options;
    $index = new Index($options);

    // Prepare some info to display.
    $status['count'] = $index->getCount();
    $status['progress'] = 'fine';

    // Prepare some more info to display.
    $items = $index->getItems();
    print_r($items);

    $index->dumpItems();
  }

}
