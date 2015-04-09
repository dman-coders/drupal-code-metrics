<?php
/**
 * @file
 * Handle CLI invocation.
 *
 * This handles the bits that are specific to being run by hand - the parsing
 * of commandline arguments.
 */

require_once "bootstrap.php";

/**
 * Class DrupalCodeMetrics_CLI.
 */
class DrupalCodeMetrics_CLI {

  private $options;
  private $defaultOptions;
  private $defaultShortOptions;
  private $args;

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
   **/
  public function process() {
    // Initialize the index, which is both the worker
    // and the interface to the database.
    $index = new DrupalCodeMetrics_Index();

    // Fetch the list of expected commandline options from the Index object
    // definition itself.
    // We don't know what additional options may eventually be added, so
    // let it tell us.
    $this->defaultOptions = $index->defaultOptions();
    $this->getCommandlineArguments();
    // $this->options and $this->args are now set.
    //
    $index->setOptions($this->options);
    foreach ($this->args as $path) {
      $index->indexFolder($path);
    }
    if ($this->options['verbose']) {
      echo $index->getItems();
    }
  }

  /**
   * Get options and args from the CLI context.
   *
   * Sets $this->args and $this->options.
   */
  private function getCommandlineArguments() {
    // Extract the expected --options from context and set them on this object.
    $longopts  = array();
    foreach ($this->defaultOptions as $key => $val) {
      $longopts[] = $key . "::";
    }
    $this->options = getopt(implode("", $this->defaultShortOptions), $longopts) + $this->defaultOptions;

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
