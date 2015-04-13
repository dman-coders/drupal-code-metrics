<?php
/**
 * @file
 * Console application - for interacting with the index.
 *
 * Set up so that we can do some common configs (like the database connection)
 * without repeating them in each command.
 *
 * This application is intended to be invoked by the trigger script in 'bin'.
 *
 * It should provides access to the default command,
 * but still lets you call the other ones too.
 *
 * http://symfony.com/doc/current/components/console/single_command_tool.html
 *
 * But the instructins there don't say how to do both at once, like
 * run a default command AND invoke others by name.
 * So we are stuck with the command namespace syntax.
 */

namespace DrupalCodeMetrics;

use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * TextUI frontend
 */
class Application extends AbstractApplication {
  /**
   * @var string
   */
  const NAME = 'Drupal Console';
  /**
   * @var string
   */
  const VERSION = '0.7.6';

  public $options;

  public function __construct() {
    parent::__construct($this::NAME, sprintf('%s', $this::VERSION));

    // I can declare global options.
    $this->getDefinition()->addOption(
      new InputOption(
        '--no-debug',
        null,
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
    // which is used when using the --help option
    $defaultCommands = parent::getDefaultCommands();
    // Load the available command definitions.
    $defaultCommands[] = new Command\ReportCommand();
    $defaultCommands[] = new Command\IndexCommand();
    $defaultCommands[] = new Command\ScanCommand();
    return $defaultCommands;
  }

  /**
   * Runs the current application.
   *
   * @param InputInterface $input An Input instance
   * @param OutputInterface $output An Output instance
   *
   * @return integer 0 if everything went fine, or an error code
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    parent::doRun($input, $output);
  }
}
