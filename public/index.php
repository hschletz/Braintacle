<?php
/**
 * All interaction with the user agent starts with this script.
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
 */

// Set up PHP environment. The error configuration is done as early as possible.
error_reporting(-1);

require_once('../module/Library/Application.php');

if (\Library\Application::isProduction()) {
    ini_set('display_errors', false);
    ini_set('display_startup_errors', false);
} else {
    ini_set('display_errors', true);
    ini_set('display_startup_errors', true);
}

\Library\Application::init(
    (getenv('BRAINTACLE_CONFIG') ?: (__DIR__ . '/../config/braintacle.ini')),
    'Console'
);
