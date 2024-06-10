#!/usr/bin/php
<?php
/**
 * Braintacle command line tools collection
 */

use Braintacle\Container;
use Tools\Application;

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$container = new Container();
$container->get(Application::class)->run();
