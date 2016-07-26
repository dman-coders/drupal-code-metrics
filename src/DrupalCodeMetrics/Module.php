<?php

namespace DrupalCodeMetrics;

/**
 * A Module, defined by its name, location and version.
 *
 * Modules with the same name but different versions are different.
 * Ones with the same name and version but different locations are
 * assumed to be identical.
 *
 * @Entity @Table(name="module")
 */
class Module {
  use LoggableTrait;
  use AutoGetSetTrait;

  /**
   * @Id
   * @Column(type="integer")
   * @GeneratedValue
   */
  public $id;

  /**
   * Machine name.
   *
   * @Column(type="string")
   *
   * @var string
   */
  public $name;

  /**
   * Human name.
   *
   * @Column(type="string", nullable=true)
   *
   * @var string
   */
  public $label;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  public $description;

  /**
   * @Column(type="string", nullable=true)
   * @var string
   */
  public $version;

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

  /**
   * @var $modified
   *   Note if I am in need of a save or not.
   */
  protected $modified;

  /**
   *
   */
  public function getFilecount($flush = FALSE) {
    if (isset($this->filecount) && !$flush) {
      return $this->filecount;
    }
    $tree = $this->getDirectoryTree();
    $this->filecount = count($tree);
    return $this->filecount;
  }

  /**
   *
   */
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
    if (!is_array($extensions)) {
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
   * @param string $prefix
   *   Internal use.
   *
   * @return array
   *   Files keyed by filepath relative to location.
   *   Values are the absolute filepath.
   */
  public function getDirectoryTree($location = NULL, $prefix = '') {
    if (!$location) {
      $location = $this->location;
    }
    $files = array();
    $di = new \DirectoryIterator($location);
    foreach ($di as $file) {
      if ($file->isDot()) {
        continue;
      }
      if ($file->isDir() === TRUE) {
        $foldername = $file->getFilename();
        $files += $this->getDirectoryTree($file->getPathname(), $foldername . DIRECTORY_SEPARATOR);
      }
      else {
        $files[$prefix . $file->getFilename()] = $file->getPathname();
      }
    }
    return $files;

  }

  /**
   * Add a value to our status field.
   *
   * Status ids will be like "info:processed" or "codereview:failed".
   * Internally they get serialized into a string for searching.
   *
   * @param $status
   */
  public function addStatus($status) {
    $statuslist = $this->getStatuslist();
    list($process, $stat) = explode(':', $status);
    $statuslist[$process][$stat] = TRUE;
    $this->setStatuslist($statuslist);
    return $this;
  }

  /**
   *
   */
  public function removeStatus($status) {
    $statuslist = $this->getStatuslist();
    list($process, $stat) = explode(':', $status);
    unset($statuslist[$process][$stat]);
    $this->setStatuslist($statuslist);
    return $this;
  }

  /**
   * See if our status includes a stat for the named scan.
   *
   * @param $scan
   */
  public function checkStatus($scan) {
    $statuslist = $this->getStatuslist();
    if (isset($statuslist[$scan])) {
      return $statuslist[$scan];
    }
    return NULL;
  }

  /**
   * Explode the status (concatenated string) into different statuses.
   *
   * Internal status may be "info:processed,style:processed,style:warnings"
   *
   * @return array
   *   a nested structure of stats,
   *   - info
   *   - - processed
   *   - style
   *   - - processed
   *   - - warnings
   */
  protected function getStatuslist() {
    $statii = explode(',', $this->status);
    $statuslist = array();
    foreach ($statii as $status) {
      @list($process, $stat) = explode(':', $status);
      $statuslist[$process][$stat] = TRUE;
    }
    return $statuslist;
  }

  /**
   * Convert a structured list of stats into a status string and serialize it.
   *
   * @param $statuslist
   *
   * @return mixed
   */
  protected function setStatuslist($statuslist) {
    $flatlist = array();
    foreach ($statuslist as $process => $stats) {
      foreach ($stats as $stat => $flag) {
        if ($flag) {
          $key = "${process}:${stat}";
          $flatlist[] = $key;
        }
      }
    }
    $this->status = implode(',', $flatlist);
    $this->modified = TRUE;
    return $statuslist;
  }

}
