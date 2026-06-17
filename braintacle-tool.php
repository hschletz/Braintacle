#!/usr/bin/php
<?php
/**
 * Braintacle command line tools collection
 */

use Braintacle\Cli\Container;
use Braintacle\Cli\ToolsApplication;

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$container = new Container();
/** @var ToolsApplication */
$application = $container->get(ToolsApplication::class);
$exitCode = $application->run();

exit($exitCode);
