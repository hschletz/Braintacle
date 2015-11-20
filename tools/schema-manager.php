#!/usr/bin/php
<?php
/**
 * Update the database schema and adjust some data to the new schema.
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package Tools
 */
/**
 * This script updates Braintacle's database schema.
 *
 * Run this script every time the schema has changed. It is safe to run it more
 * than once, even if the schema has not changed. However, it won't hurt to
 * back up your database first.
 */

// All paths are relative to this script's parent directory
$basepath = realpath(dirname(__DIR__));

require_once "$basepath/module/Library/Application.php";
\Library\Application::init('Cli');
$serviceManager = \Library\Application::getService('ServiceManager');

// Set up logger
$formatter = new \Zend\Log\Formatter\Simple('%priorityName%: %message%');
$writer = new \Zend\Log\Writer\Stream('php://stderr');
$writer->setFormatter($formatter);
$logger = $serviceManager->get('Library\Logger');
$logger->addWriter($writer);

$schemaManager = new \Database\SchemaManager($serviceManager);
$schemaManager->updateAll();
