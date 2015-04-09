<?php
/**
 * @file
 * Just some experimental code to test adding things etc.
 *
 * DO NOT RUN THIS, it's devel tesing only.
 */
require_once "bootstrap.php";


#$path = $argv[1];
$options = array(
  'database' => $conn,
);

$index = new DrupalCodeMetrics_Index($options);

$index->enqueueFolder('a/dummy/location_modulename');

$status['count'] = $index->getCount();

$status['items'] = $index->getItems();

#print_r($index);
#print_r((array) $index);
print_r($status);
