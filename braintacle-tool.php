#!/usr/bin/php
<?php
/**
 * Braintacle command line tools collection
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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
 */

error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

$mvcApplication = \Library\Application::init('Tools');
$consoleApplication = new ZF\Console\Application(
    'Braintacle command line tool',
    \Library\FileObject::fileGetContents(__DIR__ . '/VERSION'),
    $mvcApplication->getConfig()['tool_routes'],
    null,
    new Tools\Dispatcher($mvcApplication->getServiceManager())
);

exit($consoleApplication->run());
