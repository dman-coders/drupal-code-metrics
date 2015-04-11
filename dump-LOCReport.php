<?php
/**
 * @file
 *
 * Extract the Lines-Of-Code analysis from the database;
 */
require_once "bootstrap.php";
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . '/src'));
$entityManager = EntityManager::create($conn, $config);

$itemRepository = $entityManager->getRepository("DrupalCodeMetrics\\LOCReport");
$items = $itemRepository->findAll();

print_r($items);

// Flatten and serialize to JSON.
// Return label, metric1, metric2, colour, size.
$graph_data = array(
    array(
        'Name', 'Complexity', 'Maintainability', '?', 'Size',
    )
);
foreach ($items as $item) {
    // How many doc lines per line of code.
    $doc_ratio = $item->getcloc() /$item->getloc();
    // I don't like complexity per loc.
    // $complexity = $item->getccnByLloc();

    // Remake that as complexity per number of functions.
    $total_complexity = $item->getccnByLloc() * $item->getLloc();
    $complexity = $total_complexity / ($item->getFunctions() + $item->getMethods());

    $graph_data[] = array(
        $item->getName(),
        round($complexity,1),
        round($doc_ratio * 100), // complexity
        'what',
        $item->getloc()
    );
}
print_r(json_encode($graph_data));
print "\n\n";
file_put_contents('loc.json', json_encode($graph_data));
