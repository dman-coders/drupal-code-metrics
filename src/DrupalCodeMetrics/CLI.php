<?php
/**
 * @file
 * Handle CLI invocation.
 *
 * This handles the bits that are specific to being run by hand - the parsing
 * of commandline arguments.
 *
 * TODO - See if we can use found Symfony\Component\Console\Application instead.
 * http://symfony.com/doc/current/components/console/introduction.html
 */

namespace DrupalCodeMetrics;

require_once "bootstrap.php";

/**
 * Class DrupalCodeMetrics_CLI.
 */
class CLI {
  use LoggableTrait;

  private $options = array('verbose' => TRUE);
  private $defaultOptions = array();
  private $defaultShortOptions = array();
  private $args = array();
  private $index ;

  /**
   * Constructs a CLI context.
   *
   * Initializes objects with the current options.
   */
  public function __construct($options = array()) {
    $this->defaultOptions = $options;
    $this->defaultShortOptions = array(
      "v",
    );
  }

  /**
   * Run it.
   */
  public function process() {
    // Initialize the index, which is both the worker
    // and the interface to the database.
    $this->index = new Index();

    // Fetch the list of expected commandline options from the Index object
    // definition itself.
    // We don't know what additional options may eventually be added, so
    // let it tell us.
    $this->defaultOptions = $this->index->defaultOptions();
    $this->getCommandlineArguments();
    // $this->options and $this->args are now set.
    //
    $this->index->setOptions($this->options);

    if ($this->options['index']) {
      foreach ($this->args as $path) {
        $this->index->indexFolder($path);
      }
    }

    if ($this->options['tasks']) {
      $this->runTasks();
    }

    if ($this->options['dump']) {
      $this->index->dumpItems();
    }
  }

  /**
   * Loop over the remaining queued tasks.
   */
  public function runTasks() {
    print __FUNCTION__;
    $this->index->runTasks();
  }

  /**
   * Get options and args from the CLI context.
   *
   * Sets $this->args and $this->options.
   */
  private function getCommandlineArguments() {
    // Extract the expected --options from context and set them on this object.
    $longopts = array();
    foreach ($this->defaultOptions as $key => $val) {
      // Some opts ( --verbose, --flush ) take no args,
      // some ( --extensions='php,module') do.
      // figure out which depending on whether the default val
      // is a string or a bool.
      if (is_bool($val)) {
        $longopts[] = $key;
      }
      elseif (is_scalar($val)) {
        $longopts[] = $key . "::";
      }
    }
    // getopt() is a really weak-sauce utility.
    $cli_options = getopt(implode("", $this->defaultShortOptions), $longopts);
    // The flags with no opts (eg --verbose) come back set, but empty.
    // We really need them to be TRUE.
    foreach ($this->defaultOptions as $key => $val) {
      // Some opts ( --verbose, --flush ) take no args,
      if (is_bool($val) && isset($cli_options[$key])) {
        $cli_options[$key] = TRUE;
      }
    }
    // Why are cli opts such a pain to deal with?
    $this->log($cli_options, 'Commandline options given');
    $this->options = $cli_options + $this->defaultOptions;

    // Filter the remaining args.
    $all_args = $_SERVER['argv'];
    $this->args = array();
    array_shift($all_args);
    foreach ($all_args as $val) {
      if (substr($val, 0, 1) != '-') {
        $this->args[] = $val;
      }
    }
  }

}
