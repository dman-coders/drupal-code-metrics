<?php
/**
 * @file
 * Definition of an 'Index' Class.
 *
 *
 *
 */

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * An Index is a container for all the modules.
 */
class DrupalCodeMetrics_Index
{

  private $options;
  private $args;
  public $entityManager;

  /**
   * @Column(type="datetime", nullable=true)
   * @var DateTime
   */
  protected $updated;



  /**
   * Constructs an index.
   *
   * The index is the promary way of talking to the database,
   * so it can own the DB handle.
   * Or, as it's called, the $entity_manager;
   */
  public function __construct($options = array()) {
    $this->options = $options + $this->defaultOptions();

    // Scan the current directory to find our serializable objects -
    // the Module object schema definition.
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__), $this->options['is_dev_mode']);

    // Obtaining the entity manager.
    $this->entityManager = EntityManager::create($this->options['database'], $config);
  }

  /**
   * @return int
   *   Count.
   */
  public function getCount() {
    return 43;

  }

  /**
   * Retrieve all items in the index so far.
   */
  public function getItems() {
    $itemRepository = $this->entityManager->getRepository('DrupalCodeMetrics_Module');
    $items = $itemRepository->findAll();
    return $items;
  }

  /**
   * Dump all items in the index so far.
   */
  public function dumpItems() {
    $items = $this->getItems();
    foreach ($items as $item) {
      echo sprintf(" %-30s %-5s \n", $item->getName(), $item->getStatus());
    }
  }

  /**
   * Scan the given folder and add all projects we find to the index.
   *
   * @param string $path
   *   Path to scan.
   */
  public function indexFolder($dir) {
    if (! is_dir($dir)) {
      throw new Exception("'$dir' is not a folder or could not be found.");
    }
    $dir = rtrim($dir, '/');

    if ($this->options['verbose']) {
      error_log("Indexing $dir \n");
    }
    // Recurse through the folder listings.
    // When we find an info file, mark that as a project root.
    $mask = '/\.info$/';
    // Record that in our record set.
    $found = array();
    $dirs = array();
    $all = array();
    if ($handle = opendir($dir)) {
      while (FALSE !== ($filename = readdir($handle))) {
        if ($filename[0] == '.') {
          continue;
        }
        $uri = "$dir/$filename";
        $all[] = $uri;
        if (preg_match($mask, $filename, $matches)) {
          $found[] = $uri;
        }
        if (is_dir($uri)) {
          $dirs[] = $uri;
        }
      }
      closedir($handle);
    }

    // If I found an info file, note it.
    if (!empty($found)) {
      foreach ($found as $info_file) {
        print "** Found project $info_file \n";

      }
      // Generally there is one module per project folder, but occasinoally
      // they are messier. eg metatags_quick has 3 .info files.
      // Either way, we shall assume the foldername is the projectname.
      $location = dirname(reset($found));
      $this->enqueueFolder($location);
    }
    else {
      // Otherwise recurse through the directories.
      foreach ($dirs as $subdir) {
        $this->indexFolder($subdir);
      }
    }
  }

  /**
   * Register the given path as a module project to look at.
   *
   * Does not immediately process it, but flags it as pending and gives it a
   * timestamp.
   *
   * @param $location
   *   Folder name.
   */
  function enqueueFolder($location) {
    // First, see if we already know about it.

    // If not, make a location entry and serialize it.

    $module = new DrupalCodeMetrics_Module();
    $module->setName(basename($location));
    $module->setLocation($location);
    $now = new DateTime();
    $module->setUpdated($now);
    $module->setStatus('pending');

    $this->entityManager->persist($module);
    $this->entityManager->flush();

  }

  /**
   * Drop the current database and start again.
   */
  public function rebuild() {
    // TODO.
    // for now
    // vendor/bin/doctrine orm:schema-tool:drop --force
    // vendor/bin/doctrine orm:schema-tool:create
    #$this->entityManager->create();
  }

  /**
   * Define the expected option parameters.
   *
   * This list is used to extract info from the commandline,
   * as well as to pre-set useful defaults.
   *
   * @return array
   *   Options.
   */
  public function defaultOptions() {
    return array(
      'extensions' => 'module,php,inc',
      'verbose' => TRUE,
      'database' => array(
        'driver' => 'pdo_sqlite',
        'path' => 'db.sqlite',
      ),
      'is_dev_mode' => TRUE,
    );
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
