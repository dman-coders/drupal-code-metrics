<?php
/**
 * @file
 *   Adds automatic accessor methods to a class.
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
    $varname = strtolower(substr($operation, 3));
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

}
