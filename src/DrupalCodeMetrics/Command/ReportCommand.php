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

  private $output;
  private $index;

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
      )
      ->addOption(
        'locreport',
        NULL,
        InputOption::VALUE_NONE,
        'Include the  (Lines of Code) complexity report from PHPLoC'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output = $output;
    if ($input->getOption('format')) {
    }

    $output->writeln("Dump the contents of the tables");

    $options = $this->getApplication()->options;
    $this->index = new Index($options);

    // Prepare some more info to display.
    $items = $this->index->getItems();
    print_r($items);

    $this->dumpItems();

    if ($input->getOption('locreport')) {
      $locReports = $this->index->getLocReports();
      print_r($locReports);
    }
  }

  /**
   * Dump summary of all items in the index so far.
   */
  public function dumpItems() {
    $items = $this->index->getItems();
    $width = exec('tput cols');
    foreach ($items as $item) {
      $out = substr(sprintf(" %-10s %-30s %-15s %-5s", $item->getID(), $item->getName(), $item->getVersion(), $item->getStatus()), 0, $width);
      $this->output->writeln($out);
    }
  }

}
