<?php

namespace DrupalCodeMetrics;

use SebastianBergmann\PHPLOC\Analyser;

/**
 * @Entity @Table(name="locReports")
 */
class LOCReport {
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
   * @Column(type="float", nullable=true) */   protected $files;
  /**
   * @Column(type="float", nullable=true) */   protected $loc;
  /**
   * @Column(type="float", nullable=true) */   protected $lloc;
  /**
   * @Column(type="float", nullable=true) */   protected $llocClasses;
  /**
   * @Column(type="float", nullable=true) */   protected $llocFunctions;
  /**
   * @Column(type="float", nullable=true) */   protected $llocGlobal;
  /**
   * @Column(type="float", nullable=true) */   protected $cloc;
  /**
   * @Column(type="float", nullable=true) */   protected $ccn;
  /**
   * @Column(type="float", nullable=true) */   protected $ccnMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $interfaces;
  /**
   * @Column(type="float", nullable=true) */   protected $traits;
  /**
   * @Column(type="float", nullable=true) */   protected $classes;
  /**
   * @Column(type="float", nullable=true) */   protected $abstractClasses;
  /**
   * @Column(type="float", nullable=true) */   protected $concreteClasses;
  /**
   * @Column(type="float", nullable=true) */   protected $functions;
  /**
   * @Column(type="float", nullable=true) */   protected $namedFunctions;
  /**
   * @Column(type="float", nullable=true) */   protected $anonymousFunctions;
  /**
   * @Column(type="float", nullable=true) */   protected $methods;
  /**
   * @Column(type="float", nullable=true) */   protected $publicMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $nonPublicMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $nonStaticMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $staticMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $constants;
  /**
   * @Column(type="float", nullable=true) */   protected $classConstants;
  /**
   * @Column(type="float", nullable=true) */   protected $globalConstants;
  /**
   * @Column(type="float", nullable=true) */   protected $testClasses;
  /**
   * @Column(type="float", nullable=true) */   protected $testMethods;
  /**
   * @Column(type="float", nullable=true) */   protected $ccnByLloc;
  /**
   * @Column(type="float", nullable=true) */   protected $llocByNof;
  /**
   * @Column(type="float", nullable=true) */   protected $methodCalls;
  /**
   * @Column(type="float", nullable=true) */   protected $staticMethodCalls;
  /**
   * @Column(type="float", nullable=true) */   protected $instanceMethodCalls;
  /**
   * @Column(type="float", nullable=true) */   protected $attributeAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $staticAttributeAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $instanceAttributeAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $globalAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $globalVariableAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $superGlobalVariableAccesses;
  /**
   * @Column(type="float", nullable=true) */   protected $globalConstantAccesses;

  /**
   * @param array $analysis
   *   All the values from a LOC Analyser.
   */
  public function setAnalysis($analysis) {
    // $this->analysis = $analysis;.
    foreach ($analysis as $key => $val) {
      $this->$key = $val;
    }
  }

  /**
   * Runs PHP LinesOfCode analysis.
   *
   * Https://github.com/sebastianbergmann/phploc.
   *
   * @param \DrupalCodeMetrics\Module $module
   * @param $extensions
   * @return array|null
   */
  function getLocAnalysis(Module $module, $extensions) {
    // Run phploc analyser directly as PHP.
    $analyser = new Analyser();
    // It's my job to set the parameters right, and take care to only give it
    // PHP files (it borks on binaries, understandably).
    $tree = $module->getCodeFiles($extensions);
    $analysis = NULL;
    try {
      $analysis = $analyser->countFiles($tree, TRUE);
    }
    catch (Exception $e) {
      $message = "When processing " . $module->getLocation() . " " . $e->getMessage();
      error_log($message);
    }

    return $analysis;
  }

}
