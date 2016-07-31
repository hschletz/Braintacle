#!/usr/bin/php
<?php
/**
 * Update the database schema and adjust some data to the new schema.
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 *
 * There is an optional parameter for the log level (default: info):
 *
 * --loglevel=emerg|alert|crit|err|warn|notice|info|debug
 */

require_once __DIR__ . "/../vendor/autoload.php";
\Library\Application::init(__DIR__ . '/../config/braintacle.ini', 'DatabaseManager')->run();
