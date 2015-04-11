<?php
/**
 * @file
 * Setup application for the first time.
 *
 */
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once "../../vendor/autoload.php";

// Create a simple "default" Doctrine ORM configuration for Annotations.
$is_dev_mode = TRUE;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/../../src"), $is_dev_mode);

// Database configuration parameters.
$conn = array(
  'driver' => 'pdo_sqlite',
  'path' => __DIR__ . '/top100.sqlite',
);

// Obtaining the entity manager.
$entity_manager = EntityManager::create($conn, $config);
