#!/usr/bin/php
<?php
/**
 * Export all clients as XML
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

error_reporting(-1);
// Only set 1 of these options to prevent duplicate messages on the console
ini_set('display_errors', true);
ini_set('log_errors', false);
if (extension_loaded('xdebug')) {
    xdebug_disable(); // Prevents printing backtraces on validation errors
}

require_once __DIR__ . '/../vendor/autoload.php';
\Library\Application::init(__DIR__ . '/../config/braintacle.ini', 'Export')->run();
