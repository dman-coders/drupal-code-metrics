<?php
/**
 * @file
 * Definition of an 'Index' Class.
 *
 * This object represents the indexer - the engine that enumerates items
 * And stores summaries in the DB.
 *
 * (Unrelated to website index.php filename convention.)
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
  use AutoGetSetTrait;

  private $options;
  private $args;

  /**
   * @var \Symfony\Component\Console\Helper\ProgressBar
   */
  private $progress;

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
      'flush' => FALSE,
      'max-tasks' => 5,
      'database' => array(
        'driver' => 'pdo_sqlite',
        'path' => 'db.sqlite',
      ),
      'is-dev-mode' => TRUE,
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
   * The index is the primary way of talking to the database,
   * It is the engine you interact with most.
   * So it can own the DB handle.
   * Or, as it's called, the $entity_manager;
   */
  public function __construct($options = array()) {
    include_once 'drupal.inc';

    $this->options = $options + $this->defaultOptions();

    // Scan the current directory to find our serializable objects -
    // the Module object schema definition.
    $config = Setup::createAnnotationMetadataConfiguration(array(__DIR__), $this->options['is-dev-mode']);

    // Obtaining the entity manager.
    $this->entityManager = EntityManager::create($this->options['database'], $config);
  }

  /**
   * List the available scans. These will be used as keywords in the status.
   */
  public function listScans() {
    return array(
      'info',
      'content',
      'loc',
      'sniff',
      'hacked',
    );
  }

  /**
   * Get the (one) item that matches the coditions.
   *
   * @param array $conditions
   *   EG array('name' => 'views', 'version' => '7.x-2.16')
   *
   * @return null|Module
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
   * Reset the status of all items.
   *
   */
  public function resetAllStatus() {
    $qb = $this->entityManager->createQueryBuilder();
    $qb->update('DrupalCodeMetrics\Module', 'm')
        ->set('m.status', "''");
    return $qb->getQuery()->execute();
  }

  /**
   * Find a queued task that needs processing.
   *
   * Find the next job that is neither 'failed' nor 'complete'
   */
  public function getNextTask($status = 'pending') {
    $qb = $this->entityManager->createQueryBuilder();
    $qb->select('R.name', 'R.version', 'R.status')
      ->from('DrupalCodeMetrics\Module', 'R')
      ->andWhere("(NOT R.status LIKE '%failed%')")
      ->andWhere("(NOT R.status LIKE '%complete%')")
      ->orderBy('R.updated', 'ASC')
      ->setMaxResults(1);

#    $qb->setParameter('failed', 'failed');
#    $qb->setParameter('complete', 'complete');

    return $qb->getQuery()->getOneOrNullResult();
  }

  /**
   * Scan the given folder and add all projects we find to the index.
   *
   * @throws \Exception
   *   on FileNotFound.
   *
   * @param string $dir
   *   Path to scan.
   */
  public function indexFolder($dir) {
    if (!is_dir($dir)) {
      throw new \Exception("'$dir' is not a folder or could not be found.");
    }
    $dir = rtrim($dir, '/');

    $this->log("Indexing $dir", 'progress');

    // Recurse through the folder listings.
    // When we find an info file, mark that as a project root.
    $mask = '/\.info$/';
    // Record that in our record set.
    $found = array();
    $dirs = array();
    if ($handle = opendir($dir)) {
      while (FALSE !== ($filename = readdir($handle))) {
        if ($filename[0] == '.') {
          continue;
        }
        $uri = "$dir/$filename";
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
        $this->log("** Found project $info_file", '', 2);
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
    $module->setStatus('queue:pending');

    // Extract basic .info name and version.
    $this->runInfoScan($module);

    // If this Module+version exists in the DB already, don't save.
    $conditions = array('name' => $module->getName(), 'version' => $module->getVersion());
    $found = $this->findItem($conditions);
    if ($found) {
      if ($this->options['flush']) {
        // Remove the pre-existing one.
        $this->entityManager->remove($found);
      }
      else {
        $conditions['location'] = $found->getLocation();
        $conditions['status'] = $found->getStatus();
        error_log(strtr("Have already registered module name version at location , Status is status. Not re-enqueing it. Pass in the --flush flag to reset and force a re-scan.", $conditions));
        return;
      }
    }

    $this->entityManager->persist($module);
    $this->entityManager->flush();
  }


  /**
   * Retrieve queued tasks - Modules in a 'pending' state - and scan them.
   *
   * For every incomplete module, find a task we can do on it,
   * and run just that one task.
   * After running that task, go back to the queue for more.
   * This may give us the same module again with a new task.
   * Repeat until done.
   *
   * This is a queuing engine - but one which stores all its state in the data
   * objects themselves.
   * - Get me somebody who needs something done
   * - figure out what needs to be done
   * - do it
   * - throw it back in the pile
   *
   * The $max_tasks limit will only process so many tasks at once.
   *
   * @param \Symfony\Component\Console\Helper\ProgressBar $progress
   *   Optional Progress ticker.
   */
  public function runTasks() {
    $max_tasks = $this->options['max-tasks'];
    $scans = $this->listScans();
    $this->log("Starting to run tasks, processing anything incomplete in the queue. Only running $max_tasks tasks at a time, to avoid overload.");

    while ($max_tasks && ($task = $this->getNextTask())) {
      $module = $this->findItem($task);
      $this->log($task, "Running next available task on '$module->name', The batch job instructions are ", 2);

      // Figure out which scans on this item have not been done yet.
      $scan_to_run = '';
      foreach ($scans as $scan) {
        $scanstatus = $module->checkStatus($scan);
        if (empty($scanstatus)) {
          // Have not run the $scan scan yet.
          $scan_to_run = $scan;
          break;
        }
        else {
          // $scan scan was run already.
          // $this->log("'$module->name' status has already run '$scan' scan");
        }
      }
      if (!empty($scan_to_run)) {
        $this->runScan($module, $scan_to_run);
      }
      else {
        // If we are here, I guess all tasks possible for this module are done.
        $module->removeStatus('queue:pending');
        $module->addStatus('queue:complete');
        $this->entityManager->persist($module);
        $this->entityManager->flush();
      }

      $max_tasks --;
      if ($this->progress) {
        // Console progressbar.
        $this->progress->advance();
      }
    }
  }

  /**
   * Execute the named scan on the named module.
   *
   * Will always update the module with a status change.
   *
   * @param Module $module
   * @param $scan
   */
  function runScan(Module $module, $scan) {
    // Deduce magic function nam, then try to invoke it.
    // This means that as new scan types get added, they an be run
    // just by being named correctly..
    $funcname = "run" . ucfirst($scan) . "Scan";
    if (method_exists($this, $funcname)) {
      $this->log("Running '$scan' scan on '$module->name'", 'progress');
      $this->$funcname($module);
    }
    else {
      $this->log("No expected function $funcname available yet.", '', 2);
      $module->addStatus("$scan:unavailable");
    }
    $this->entityManager->persist($module);
    $this->entityManager->flush();
  }

  /**
   * Extract metadata from the Module .info file.
   *
   * @param Module $module
   *
   * @return Module
   */
  public function runInfoScan(Module $module) {
    // Extract basic info and register the module.
    if ($info_file = $this->findInfoFile($module->getLocation())) {
      $info = drupal_parse_info_file($info_file);

      // Faulty info files cause warnings for me.
      if (empty($info['name'])) {
        // Exception handling.
        $info['name'] = "Bad Info";
        $info['description'] = $info_file;
        $module->addStatus('info:failed');
      }

      $info += array('name' => "Bad info");
      $module->setLabel($info['name']);
      if (isset($info['description'])) {
        $module->setDescription($info['description']);
      }
      if (isset($info['version'])) {
        $module->setVersion($info['version']);
      }
      $module->addStatus('info:processed');
    }
    else {
      $module->addStatus('info:failed');
    }
    return $module;
  }

  /**
   * Tell the module to init info about itself.
   *
   * Check it's still valid-ish and has files and an info file.
   * The dir may have gone away in the meantime.
   *
   * @param $module
   *
   * @return Module
   */
  public function runContentScan(Module $module) {
    if (! is_dir($module->getLocation())) {
      error_log('Module Directory has gome missing.');
      $module->addStatus('content:failed-missing');
    }
    else {
      $filecount = $module->getFilecount();
      $this->log("$module->name filecount is $filecount");
      $codefilecount = $module->getCodeFilecount($this->options['extensions']);
      $this->log("$module->name codefilecount is $codefilecount");
      $module->addStatus('content:processed');
    }
    return $module;
  }

  public function runLocScan(Module $module) {
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

    if (!$analysis) {
      $this->log("LOCReport on " . $module->getName() . " failed. Not updating it.");
      $module->addStatus('loc:failed');
    }
    else {
      $module->addStatus('loc:processed');
    }
    return $this;
  }

  /**
   * Run PHP Code Sniffer over the given module.
   *
   * @param Module $module
   * @return $this
   */
  public function runSniffScan(Module $module) {
    // Look for an existing one before adding or updating.
    $conditions = array('name' => $module->getName(), 'version' => $module->getVersion());
    $identifier = implode('-', $conditions);
    $found = $this->entityManager
        ->getRepository('DrupalCodeMetrics\\SniffReport')
        ->findOneBy($conditions);

    if ($found) {
      $report = $found;
      $this->log("Updating existing Sniff report for $identifier");
    }
    else {
      $report = new SniffReport();
      $this->log("Creating new Sniff report for $identifier");
    }


    $report->setName($module->getName());
    $report->setVersion($module->getVersion());
    $now = new \DateTime();
    $report->setUpdated($now);
    $analysis = $report->getSniffAnalysis($module, $this->options['extensions']);
    $report->setAnalysis($analysis);
    $this->entityManager->persist($report);
    $this->entityManager->flush();

    if (!$analysis) {
      $this->log("SniffReport on " . $module->getName() . " failed. Not updating it.");
      $module->addStatus('sniff:failed');
    }
    else {
      $module->addStatus('sniff:processed');
    }
    return $this;
  }

  /**
   * Retrieve all items in the index so far.
   */
  public function getLocReports() {
    $itemRepository = $this->entityManager->getRepository('DrupalCodeMetrics\\LOCReport');
    $items = $itemRepository->findAll();
    return $items;
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


}
