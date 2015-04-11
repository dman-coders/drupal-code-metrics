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
