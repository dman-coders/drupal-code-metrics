<?php
/**
 * @file
 * Storage for PHP Code Sniff reports.
 */
namespace DrupalCodeMetrics;

/**
 * @Entity @Table(name="sniffReports")
 */
class SniffReport {
  use LoggableTrait;
  use AutoGetSetTrait;

  /**
   * @Id @Column(type="integer") @GeneratedValue */
  protected $id;

  /**
   * @Column(type="string") @var string
   * @ManyToOne(targetEntity="Module")
   * @JoinTable(name="Module", joinColumns={
   * @JoinColumn(name="name", referencedColumnName="name"),
   * @JoinColumn(name="version", referencedColumnName="version")
   * })
   */
  protected $name;

  /**
   * @Column(type="string", nullable=true) @var string */
  protected $version;

  /**
   * @Column(type="datetime", nullable=true) @var DateTime */
  protected $updated;

  /**
   * @Column(type="float", nullable=true) */   protected $errors;
  /**
   * @Column(type="float", nullable=true) */   protected $warnings;
  /**
   * @Column(type="float", nullable=true) */   protected $files;

  /**
   * @param array $analysis
   *   All the values from a Sniff Analyser.
   */
  public function setAnalysis($analysis) {
    if (empty($analysis)) {
      return;
    }
    if (!is_array($analysis)) {
      throw new \Exception('Invalid Analysis supplied to ' . __FUNCTION__ . '(). Expected an array.', E_NOTICE);
    }
    foreach ($analysis as $key => $val) {
      $this->$key = $val;
    }
  }

  /**
   * Runs PHP sniff analysis.
   */
  function getSniffAnalysis(Module $module, $extensions) {

    print_r(get_defined_vars());
    $verbosity = 1;
    // Run php analyser directly as PHP.
    $phpcs = new \PHP_CodeSniffer($verbosity);

    // Need to emulate a CLI environment in order to pass certain settings down
    // to the internals.
    // Decoupling here is atrocious.
    $cli = new SniffReporter();
    $phpcs->setCli($cli);

    // Parameters passed to phpcs.
    // Normally we just name the standard,
    // but passing the full path to it also works.
    $values = array(
      'standard' => 'vendor/drupal/coder/coder_sniffer/Drupal',
      'sniffs' => array(),
    );
    $phpcs->initStandard($values['standard'], $values['sniffs']);

    $analysis = NULL;
    try {
      // PHPCS handles recursion on its own.
      // $analysis = $phpcs->processFiles($module->getLocation());
      // But we have already enumerated the files, so lets keep consistent.
      $tree = $module->getCodeFiles($extensions);
      // $analysis = $phpcs->processFiles($tree);
      // processFiles is too abstract, it doesn't return the individual results.
      // Do the iteration ourselves.
      foreach ($tree as $filepath) {
        $analysis = $phpcs->processFile($filepath);
      }
    }
    catch (Exception $e) {
      $message = "When processing " . $module->getLocation() . " " . $e->getMessage();
      error_log($message);
    }

    // Params for reporting.
    $report = 'full';
    $showSources = FALSE;
    $cliValues = array(
      'colors' => FALSE,
    );
    $reportFile = 'report.out';
    $result = $phpcs->reporting->printReport($report, $showSources, $cliValues, $reportFile);

    print_r($result);
    return $analysis;
  }

}


/**
 * Class SniffReporter.
 *
 * @package DrupalCodeMetrics
 *
 * We can't influence CodeSniffer settings directly, as it takes its preferences
 * from CLI settings.
 * This object stubs the CLI so we can set settings.
 */
class SniffReporter extends \PHP_CodeSniffer_CLI {

  /**
   * Get a list of default values for all possible command line arguments.
   *
   * @return array
   */
  public function getDefaults() {
    $defaults = parent::getDefaults();
    $defaults['standard'] = array('Drupal');
    // The Sniffer tries really hard to discard its logs
    // ALL the time unless we tell it not to.
    // All these settings are just to prevent that amnesia.
    $defaults['showSources'] = TRUE;
    $defaults['verbosity'] = 1;
    $defaults['reports'] = array('full' => NULL);
    $defaults['warningSeverity'] = PHPCS_DEFAULT_WARN_SEV;
    // If the severities are left as the default (zero) then
    // NOTHING is considered worth logging or even counting!
    $this->warningSeverity = PHPCS_DEFAULT_WARN_SEV;
    $this->errorSeverity = PHPCS_DEFAULT_ERROR_SEV;

    return $defaults;

  }//end getDefaults()

  /**
   * Gets the emulated command line values.
   *
   * This is a stub, Always return our defaults.
   *
   * @return array
   */
  public function getCommandLineValues() {
    if (empty($this->values) === FALSE) {
      return $this->values;
    }

    $this->values = $this->getDefaults();

    return $this->values;
  }

}
