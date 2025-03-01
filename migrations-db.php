<?php

/**
 * Included by Doctrine Migrations CLI.
 */

use Braintacle\Container;
use Doctrine\DBAL\Connection;

require_once './vendor/autoload.php';

$container = new Container();

return $container->get(Connection::class);
