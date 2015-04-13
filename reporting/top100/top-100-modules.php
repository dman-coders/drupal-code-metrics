<?php
/**
 * Generate stats for the top 100 Drupal modules
 */

// If the page changes, this will have to change, but probably not by much.
$usage_url = "https://www.drupal.org/project/usage";
$usage_title_scrape_pattern = '#project-usage-all-projects tr > td:nth-child(2) a';

$temp_dir = 'tmp';
$update_url = "http://updates.drupal.org/release-history/";
$major_version = "7";

// Avoid these because they are different.
$skip_modules = array('drupal', 'filefield', 'imagefield', 'imagecache');


///////////////
// Fetch list of modules
///////////////
// I'm on a kick to learn new tools this week, so let's get friendly with Goutte.
// I know I could do it with file_get_contents() and DOM, but ...

require_once "bootstrap.php";

use Goutte\Client;

if (!is_dir($temp_dir)) {
  mkdir($temp_dir);
}

$client = new Client();
print("Fetching $usage_url \n");
$crawler = $client->request('GET', $usage_url);
$projects = $crawler->filter($usage_title_scrape_pattern)
  ->each(function (Symfony\Component\DomCrawler\Crawler $node) {
  $url = $node->attr('href');
  $name = basename($url);
  $label = $node->text();
  printf("%-30s %s\n", $name, $label);
  return array(
    'url'   => $url,
    'name'  => $name,
    'label' => $label,
  );
});

///////////////
// Download copy of modules
///////////////

// Use drush now, it knows how to choose the most current version.
foreach ($projects as $rank => $project) {
  if (in_array($project['name'], $skip_modules)) {
    continue;
  }
  $target_dir = "$temp_dir/${project['name']}";
  $drush_opts = "--destination=${temp_dir} --default-major=${major_version} ";
  printf("%-5i %s\n", $rank, $project['label']);
  if (!is_dir($target_dir)) {
    $command = "drush dl -y $drush_opts ${project['name']} ";
    print "$command \n";
    passthru($command);
  }
  else {
    print "$target_dir exists already. Delete it if you want a refresh.\n";
  }
}

echo "Downloaded a set of the most popular modules. Index them now.\n";


///////////////
// index and summarize all modules
///////////////

// Run a whole index job independant of the normal index.

// Start with our own take on bootstrap.
require_once "../../vendor/autoload.php";
require_once "bootstrap.php";

// Now set up an alternate index with its own DB.
$options = array(
  'database'  => $conn,
  'max-tasks' => 50,
);
$index = new DrupalCodeMetrics\Index($options);
#$index->setOptions($options);
$index->indexFolder($temp_dir);
$index->runTasks();


// Now dump the results as JSON.
// TODO add this functionality to the Index

$items = $index->getLocReports();

// Flatten and serialize to JSON.
// Return label, metric1, metric2, colour, size.
$graph_data = array(
  array('Name', 'Complexity', 'Maintainability', '?', 'Size (exponential)',),
);
foreach ($items as $item) {

  if ($item->getloc() == 0) {
    error_log("No lines of code found in the analysis of " . $item->getName() . " - Not including it in the metrics.");
    continue;
  }

  // How many doc lines per line of code.
  $doc_ratio = $item->getcloc() / $item->getloc();
  // I don't like complexity per loc.
  // $complexity = $item->getccnByLloc();
  // Remake that as complexity per number of functions.
  $total_complexity = $item->getccnByLloc() * $item->getLloc();
  $complexity = $total_complexity / ($item->getFunctions() + $item->getMethods());

  // Use sqrt of size for comparison to illustrate better variety.

  $tag = 'contrib';
  if (preg_match('/^\d.\d+$/', $item->getVersion())) {
    // Only core modules have a version like '7.32'
    $tag = 'core';
  }
  $graph_data[] = array(
    $item->getName(),
    round($complexity, 2),
    round($doc_ratio * 100),
    $tag,
    sqrt($item->getloc())
  );
}
print_r(json_encode($graph_data));
print "\n\n";
file_put_contents('top100.json', json_encode($graph_data));
