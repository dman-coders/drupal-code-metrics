<?php
/**
 * @file
 * Set some defaults when working on the cli.
 *
 * For the benefit of the Doctrine command-line tool.
 *
 * With this file in place, you can now interact with the named
 * DB using the command
 * vendor/bin/doctrine
 *
 * http://doctrine-orm.readthedocs.org/en/latest/tutorials/getting-started.html
 */

require_once "bootstrap.php";
return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entity_manager);
