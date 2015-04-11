<?php
/**
 * @file
 * Definition of an 'Index' Class.
 *
 * This application runs in several phases.
 *
 * The first phase loops through the given folder structure and enumerates
 * the modules found there. It just notes them into the Modules table,
 * and does not process them immerdiately.
 *
 * The next phase then goes back to the Modules table and pops off any 'pending'
 * entries one by one, and performs the staic code analysis on it.
 *
 * This is so this process can be robustly stopped, started, restarted
 * or backgrounded without having to re-index from the top each time.
 */

namespace DrupalCodeMetrics;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

/**
 * An Index is a container for all the modules.
 */
class Index {
  use LoggableTrait;

  private $options;
  private $args;
  public $entityManager;

  const REPO = "DrupalCodeMetrics\\Module";

  /**
   * Date of the last run.
   *
   * @Column(type="datetime", nullable=true)
   * @var DateTime
   */
  protected $updated;

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
      'flush' => FALSE,
      'max_tasks' => 5,
      'database' => array(
        'driver' => 'pdo_sqlite',
        'path' => 'db.sqlite',
      ),
      'is_dev_mode' => TRUE,
      // set --index to list projects
      'index' => FALSE,
      // set --tasks to process any outstanding tasks.
      'tasks' => FALSE,
      // set --dump to list the results when done.
      'dump' => FALSE,
    );
  }

  /**
   * Constructs an index.
   *
   * The index is the promary way of talking to the database,
   * so it can own the DB handle.
   * Or, as it's called, the $entity_manager;
   */
  public function __construct($options = array()) {
    include_once 'drupal.inc';

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
   * Get the (one) item that matches the coditions.
   *
   * @param array $conditions
   *   EG array('name' => 'views', 'version' => '7.x-2.16')
   *
   * @return null|DrupalCodeMetrics_Module
   */
  public function findItem($conditions) {
    return $this->entityManager
      ->getRepository(self::REPO)
      ->findOneBy($conditions);
  }

  /**
   * Retrieve all items in the index so far.
   */
  public function getItems() {
    $itemRepository = $this->entityManager->getRepository(self::REPO);
    $items = $itemRepository->findAll();
    return $items;
  }

  /**
   * Dump all items in the index so far.
   */
  public function dumpItems() {
    $items = $this->getItems();
    foreach ($items as $item) {
      echo sprintf(" %-10s %-30s %-5s %-10s \n", $item->getID(), $item->getName(), $item->getStatus(), $item->getVersion());
    }
  }


  /**
   * Find a queued task that needs processing.
   */
  public function getNextTask($status = 'pending') {
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('R.name', 'R.version', 'R.status')
      ->from(self::REPO, 'R')
      ->where(
        $qb->expr()->eq('R.status', ":status")
      )
      ->orderBy('R.updated', 'ASC')
      ->setMaxResults(1);

    $qb->setParameter('status', $status);

    return $qb->getQuery()->getOneOrNullResult();
  }

  /**
   * Scan the given folder and add all projects we find to the index.
   *
   * @param string $dir
   *   Path to scan.
   */
  public function indexFolder($dir) {
    if (!is_dir($dir)) {
      throw new \Exception("'$dir' is not a folder or could not be found.");
    }
    $dir = rtrim($dir, '/');

    if ($this->options['verbose']) {
      $this->log("Indexing $dir");
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

  private function findInfoFile($dir) {
    $mask = '/\.info$/';
    if (!($handle = opendir($dir))) {
      return NULL;
    }
    // Info file is almost always named after the folder.
    // If not, scan and will have to take the first in fo we find.
    $info_file = $dir . '/' . basename($dir) . '.info';
    if (! file_exists($info_file)) {
      while (FALSE !== ($filename = readdir($handle))) {
        $uri = "$dir/$filename";
        if (preg_match($mask, $filename, $matches)) {
          return $info_file;
        }
      }
    }
    closedir($handle);
    return $info_file;
  }

  /**
   * Register the given path as a module project to look at.
   *
   * Does not immediately process it, but flags it as pending and gives it a
   * timestamp.
   * Returns NULL if the entry already exists, and does not add a new entry.
   *
   * @param string $location
   *   Folder name.
   */
  public function enqueueFolder($location) {
    // First, see if we already know about it.
    // If not, make a location entry and serialize it.
    $module = new Module();
    $module->setName(basename($location));
    $module->setLocation($location);
    $now = new \DateTime();
    $module->setUpdated($now);
    $module->setStatus('pending');

    if ($info_file = $this->findInfoFile($location)) {
      $info = drupal_parse_info_file($info_file);
      $module->setLabel($info['name']);
      if (isset($info['description'])) {
        $module->setDescription($info['description']);
      }
      if (isset($info['version'])) {
        $module->setVersion($info['version']);
      }
    }
    else {
      $module->setStatus('no info');
    }
    // If this Module+version exists in the DB already, don't save.
    $conditions = array('name' => $module->getName(), 'version' => $module->getVersion());
    $found = $this->findItem($conditions);
    if ($found && !$this->options['flush']) {
      $conditions['location'] = $module->getLocation();
      error_log(strtr("Have already registered module name version at location. Skipping it. Pass in the --flush flag to reset and force a re-scan.", $conditions));
      return;
    }

    $this->entityManager->persist($module);
    $this->entityManager->flush();

  }


  /**
   * Retrieve queued tasks - Modules in a 'pending' state - and scan them.
   *
   * The $max_tasks limit will only process so many Modules at once.
   */
  public function runTasks() {
    $max_tasks = $this->options['max_tasks'];
    $this->log("Starting to run tasks, processing anything 'pending' in the queue. Only running $max_tasks tasks at a time, to avoid overload.");

    while ($max_tasks && ($task = $this->getNextTask())) {
      $this->log($task, "Running");

      // The scans are run by the Module object, not from above.
      $module = $this->findItem($task);

      // Tell the module to init info about itself.
      $tree = $module->getDirectoryTree();
      $filecount = $module->getFilecount();
      $this->log("filecount is $filecount");
      $codefilecount = $module->getCodeFilecount($this->options['extensions']);
      $this->log("codefilecount is $codefilecount");

      // Run phploc analyser directly as PHP.
      $loc_report = $this->runLocReport($module);
      if (!$loc_report) {
        $this->log("LOCReport on " . $module->getName() . " failed. Not updating it.");
        $module->setStatus('failed-loc');

      }
      else {
        $module->setStatus('processed-loc');
      }

      // Flag this is done so the queue can proceed.
      $this->entityManager->persist($module);
      $this->entityManager->flush();

      $max_tasks --;
    }
  }


  public function runLocReport(Module $module) {
    // Look for an existing one before adding or updating.
    $conditions = array('name' => $module->getName(), 'version' => $module->getVersion());
    $identifier = implode('-', $conditions);
    $found = $this->entityManager
      ->getRepository('DrupalCodeMetrics\\LOCReport')
      ->findOneBy($conditions);

    if ($found) {
      $report = $found;
      $this->log("Updating existing LOC report for $identifier");
    }
    else {
      $report = new LOCReport();
      $this->log("Creating new LOC report for $identifier");
    }

    $report->setName($module->getName());
    $report->setVersion($module->getVersion());
    $now = new \DateTime();
    $report->setUpdated($now);
    $analysis = $report->getLocAnalysis($module, $this->options['extensions']);
    $report->setAnalysis($analysis);
    $this->entityManager->persist($report);
    $this->entityManager->flush();

    $this->log($report, __FUNCTION__ . " (" . $module->getName() . ")");
    return $report;
  }

  /**
   * Drop the current database and start again.
   */
  public function rebuild() {
    // TODO.
    // for now
    // vendor/bin/doctrine orm:schema-tool:drop --force
    // vendor/bin/doctrine orm:schema-tool:create
    // $this->entityManager->create();
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
