<?php
/**
 * @file
 *   Adds automatic accessor methods to a class.
 *
 * A utility that my lazy modules can call on until I feel like they deserve
 * getter and setter boilerplate.
 *
 * An object that implements this trait should also have its own $options array.
 * Preferably protected.
 */

namespace DrupalCodeMetrics;

/**
 * Adds getVariableX() and SetVariableX() functions everywhere.
 */
trait AutoGetSetTrait {

  /**
   * Magic method to catch getters and setters.
   *
   * I'm lazy, just pretend that getName and setName and that bollocks
   * works until I really need to do something special to them.
   */
  public function __call($operation, $arguments) {
    $getset = substr($operation, 0, 3);
    $rawVarName = substr($operation, 3);
    // Drop first cap, keep other caps.
    $varname = lcfirst($rawVarName);
    if ($getset == 'get') {
      return empty($this->$varname) ? NULL : $this->$varname;
    }
    elseif ($getset == 'set') {
      $this->$varname = reset($arguments);
    }
    else {
      throw new BadMethodCallException("No such method $operation on " . __CLASS__);
    }
    return $this;
  }

  /**
   * Set options in an assumed $options array.
   *
   * @param string $opt
   * @param mixed $val
   */
  public function setOption($opt, $val) {
    $this->options[$opt] = $val;
  }

  /**
   * Get options in an assumed $options array.
   *
   * @param string $opt
   */
  public function getOption($opt) {
    return $this->options[$opt];
  }

}
