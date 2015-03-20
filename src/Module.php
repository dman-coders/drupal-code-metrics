<?php
/**
 * @file
 * Definition of a 'Module' Class.
 *
 * For the benefit of Doctrine Entity DB Schema auto-configuration.
 * Describing my vars here will create a corresponding Database table.
 *
 * Following the guide at http://doctrine-orm.readthedocs.org/en/latest/tutorials/getting-started.html
 */

/**
 * A Module, defined by its name, location and version.
 *
 * Modules with the same name but different versions are different.
 * Ones with the same name and version but different locations are
 * assumed to be identical.
 *
 * @Entity @Table(name="products")
 */
class Module
{
  /**
   * @Id
   * @Column(type="integer")
   * @GeneratedValue
   */
  protected $id;

  /**
   * @Column(type="string")
   * @var string
   */
  protected $name;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $version;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $location;

  /**
   * @Column(type="datetime", nullable=true)
   * @var DateTime
   */
  protected $updated;


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

    print_r(get_defined_vars());
#    return $this->invoke($operation, $arguments);
  }
/*
  public function getId()
  {
    return $this->id;
  }

  public function getName()
  {
    return $this->name;
  }

  public function setName($name)
  {
    $this->name = $name;
  }
*/

}
