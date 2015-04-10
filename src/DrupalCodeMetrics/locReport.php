<?php
/**
 * @file
 * Storage for phploc reports.
 */
namespace DrupalCodeMetrics;

/**
 * @Entity @Table(name="locReports")
 */
class LOCReport {
  use LoggableTrait;

  /** @Id @Column(type="integer") @GeneratedValue */
  protected $id;

  /** @Column(type="string") @var string */
  protected $name;

  /** @Column(type="string", nullable=true) @var string */
  protected $version;

  /** @Column(type="datetime", nullable=true) @var DateTime */
  protected $updated;

  /** @Column(type="float", nullable=true) */   protected $files;
  /** @Column(type="float", nullable=true) */   protected $loc;
  /** @Column(type="float", nullable=true) */   protected $lloc;
  /** @Column(type="float", nullable=true) */   protected $llocClasses;
  /** @Column(type="float", nullable=true) */   protected $llocFunctions;
  /** @Column(type="float", nullable=true) */   protected $llocGlobal;
  /** @Column(type="float", nullable=true) */   protected $cloc;
  /** @Column(type="float", nullable=true) */   protected $ccn;
  /** @Column(type="float", nullable=true) */   protected $ccnMethods;
  /** @Column(type="float", nullable=true) */   protected $interfaces;
  /** @Column(type="float", nullable=true) */   protected $traits;
  /** @Column(type="float", nullable=true) */   protected $classes;
  /** @Column(type="float", nullable=true) */   protected $abstractClasses;
  /** @Column(type="float", nullable=true) */   protected $concreteClasses;
  /** @Column(type="float", nullable=true) */   protected $functions;
  /** @Column(type="float", nullable=true) */   protected $namedFunctions;
  /** @Column(type="float", nullable=true) */   protected $anonymousFunctions;
  /** @Column(type="float", nullable=true) */   protected $methods;
  /** @Column(type="float", nullable=true) */   protected $publicMethods;
  /** @Column(type="float", nullable=true) */   protected $nonPublicMethods;
  /** @Column(type="float", nullable=true) */   protected $nonStaticMethods;
  /** @Column(type="float", nullable=true) */   protected $staticMethods;
  /** @Column(type="float", nullable=true) */   protected $constants;
  /** @Column(type="float", nullable=true) */   protected $classConstants;
  /** @Column(type="float", nullable=true) */   protected $globalConstants;
  /** @Column(type="float", nullable=true) */   protected $testClasses;
  /** @Column(type="float", nullable=true) */   protected $testMethods;
  /** @Column(type="float", nullable=true) */   protected $ccnByLloc;
  /** @Column(type="float", nullable=true) */   protected $llocByNof;
  /** @Column(type="float", nullable=true) */   protected $methodCalls;
  /** @Column(type="float", nullable=true) */   protected $staticMethodCalls;
  /** @Column(type="float", nullable=true) */   protected $instanceMethodCalls;
  /** @Column(type="float", nullable=true) */   protected $attributeAccesses;
  /** @Column(type="float", nullable=true) */   protected $staticAttributeAccesses;
  /** @Column(type="float", nullable=true) */   protected $instanceAttributeAccesses;
  /** @Column(type="float", nullable=true) */   protected $globalAccesses;
  /** @Column(type="float", nullable=true) */   protected $globalVariableAccesses;
  /** @Column(type="float", nullable=true) */   protected $superGlobalVariableAccesses;
  /** @Column(type="float", nullable=true) */   protected $globalConstantAccesses;

  /**
   * @param array $analysis
   *   All the values from a LOC Analyser.
   */
  public function setAnalysis($analysis) {
    $this->analysis = $analysis;
    foreach ($analysis as $key => $val) {
      $this->$key = $val;
    }
    $this->log(__FUNCTION__);
  }

  /**
   * Magic method to catch getters and setters.
   *
   * I'm lazy, just pretend that getName and setName and that bollocks
   * works until I really need to do something special to them.
   */
  public function __call($operation, $arguments) {
    $getset = substr($operation, 0, 3);
    $varname = strtolower(substr($operation, 3));
    if ($getset == 'get') {
      return $this->$varname;
    }
    elseif ($getset == 'set') {
      $this->$varname = reset($arguments);
    }
  }

}
