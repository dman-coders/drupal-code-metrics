<?php

namespace DrupalCodeMetrics;

use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TextUI frontend.
 */
class Application extends AbstractApplication {

  /**
   * @var string
   */
  const NAME = 'Drupal Code Bitch';

  /**
   * @var string
   */
  const VERSION = '0.0.0';

  /**
   * @var []
   */
  public $options;

  /**
   *
   */
  public function __construct() {
    parent::__construct($this::NAME, sprintf('%s', $this::VERSION));

    // I can declare global options.
    $this->getDefinition()->addOption(
      new InputOption(
        '--no-debug',
        NULL,
        InputOption::VALUE_NONE,
        'Switches off debug mode.'
      )
    );

    /*
    I own the database connection configs, so that the commands don't have to.
    However I don't instantiate the entityManager itself.
    I am the application, I know where the db is.
    The commands and the Index do the actual talking to it.
     */

    // Database configuration parameters.
    // HOW TO DO THIS BETTER?
    // Need to build this on the fly if it does not exits - how?
    $this->options['database'] = array(
      'driver' => 'pdo_sqlite',
      'path' => __DIR__ . '/../../db.sqlite',
    );

  }

  /**
   * Gets the default commands that should always be available.
   *
   * @return array An array of default Command instances
   */
  protected function getDefaultCommands() {
    // Keep the core default commands to have the HelpCommand
    // which is used when using the --help option.
    $defaultCommands = parent::getDefaultCommands();
    // Load the available command definitions.
    $defaultCommands[] = new \DrupalCodeMetrics\Command\InitializeCommand();
    $defaultCommands[] = new \DrupalCodeMetrics\Command\ReportDumpCommand();
    $defaultCommands[] = new \DrupalCodeMetrics\Command\IndexListCommand();
    $defaultCommands[] = new \DrupalCodeMetrics\Command\IndexScanCommand();
    $defaultCommands[] = new \DrupalCodeMetrics\Command\IndexFlushCommand();
    return $defaultCommands;
  }

  /**
   * Runs the current application.
   *
   * @param InputInterface $input
   *   An Input instance
   * @param OutputInterface $output
   *   An Output instance
   *
   * @return integer 0 if everything went fine, or an error code
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    // Wrap this in a warning to catch database connection errors
    // And provide setup instructions.
    try {
      parent::doRun($input, $output);
    } catch (\Doctrine\DBAL\Exception $e) {
      $output->writeln('<error>Problem communication with the expected database. Reset your database with `dcm init`</error>');
      $output->writeln('See <info>help init</info> for details.');
    } catch (\Doctrine\DBAL\Exception\DatabaseObjectNotFoundException $e) {
      $output->writeln('<error>Unable to find expected object in database. Before using this tool, you should create your database with `dcm init`</error>');
      $output->writeln('See <info>help init</info> for details.');
    }

  }

}
