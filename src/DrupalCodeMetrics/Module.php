<?php
/**
 * @file
 * Definition of a 'Module' Class.
 *
 * A Module (or Drupal project) entry here contains identity information,
 * as well as the audit results and metrics.
 *
 * These metrics include line count, complexity, and summaries of code
 * analysis.
 *
 * For the benefit of Doctrine Entity DB Schema auto-configuration.
 * Describing my vars here will create a corresponding Database table.
 *
 * Following the guide at
 * http://doctrine-orm.readthedocs.org/en/latest/tutorials/getting-started.html
 */


namespace DrupalCodeMetrics;

/**
 * A Module, defined by its name, location and version.
 *
 * Modules with the same name but different versions are different.
 * Ones with the same name and version but different locations are
 * assumed to be identical.
 *
 * @Entity @Table(name="products")
 */
class Module {
  use LoggableTrait;

  /**
   * @Id
   * @Column(type="integer")
   * @GeneratedValue
   */
  protected $id;

  /**
   * Machine name.
   *
   * @Column(type="string")
   * @var string
   */
  protected $name;

  /**
   * Human name.
   *
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $label;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $description;

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
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $status;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $filecount;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $linecount;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  protected $documentationcount;


  public function getFilecount($flush = FALSE) {
    if (isset($this->filecount) && !$flush) {
      return $this->filecount;
    }
    $tree = $this->getDirectoryTree();
    $this->filecount = count($tree);
    return $this->filecount;
  }

  public function getCodeFilecount($extensions) {
    $codefiles = $this->getCodeFiles($extensions);
    $this->codefilecount = count($codefiles);
    return $this->codefilecount;
  }

  /**
   * The code files inside this project.
   *
   * @param array|string $extensions
   *   Eg "php,inc,module".
   *
   * @return array
   *   list of files. Keyed by local filename,
   */
  public function getCodeFiles($extensions) {
    if (! is_array($extensions)) {
      $extensions = explode(',', $extensions);
    }
    $tree = $this->getDirectoryTree();
    // Filter out by extension.
    $codefiles = array();
    foreach ($tree as $rel_path => $filename) {
      if (in_array(pathinfo($filename, PATHINFO_EXTENSION), $extensions)) {
        $codefiles[$rel_path] = $filename;
      }
    }
    return $codefiles;
  }

  /**
   * Lists the files inside this modules location.
   *
   * @param string $location
   *   Filepath.
   *
   * @return array
   *   Files keyed by filepath relative to location.
   */
  public function getDirectoryTree($location = NULL, $prefix = '') {
    if (! $location) {
      $location = $this->location;
    }
    $files = array();
    $di = new \DirectoryIterator($location);
    foreach ($di as $file) {
      if ($file->isDot()) {
        continue;
      }
      if ($file->isDir() === true) {
        $foldername = $file->getFilename();
        $files += $this->getDirectoryTree($file->getPathname(), $foldername . DIRECTORY_SEPARATOR);
      }
      else {
        $files[$prefix . $file->getFilename()] = $file->getFilename();
      }
    }
    return $files;

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
