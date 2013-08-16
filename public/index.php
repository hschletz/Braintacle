<?php
/**
 * All interaction with the user agent starts with this script.
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

// Report every possible error and warning
error_reporting(-1);

// Set up PHP environment. This could be done in application.ini, but the
// settings there come into effect at a later point only where it might be too
// late.
ini_set('log_errors', true);

require '../library/Braintacle/Application.php';

if (Braintacle_Application::getEnvironment() == 'production') {
    ini_set('display_errors', false);
    ini_set('display_startup_errors', false);
} else {
    ini_set('display_errors', true);
    ini_set('display_startup_errors', true);
}
ini_set('magic_quotes_runtime', false);

// Bootstrap the application
Braintacle_Application::init();
