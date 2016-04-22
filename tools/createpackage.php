#!/usr/bin/php
<?php
/**
 * Create a package from the command line
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
 * This script creates a package from the command line. This is useful if a file
 * is too big to be uploaded to the webserver. It can also be used as part of a
 * package builder script.
 *
 * It is limited to the package builder defaults. Only the name and the file
 * itself can be specified.
 *
 * Don't forget to change permissions/ownership of the generated directory and
 * files. Otherwise the webserver won't be able to read and/or delete them.
 */

error_reporting(E_ALL);

require(__DIR__ . '/../module/Library/Application.php');
\Library\Application::init(__DIR__ . '/../config/braintacle.ini', 'PackageBuilder')->run();
