<?php

/**
 * All interaction with the user agent starts with this script.
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

error_reporting(-1);
session_cache_limiter('nocache'); // Default headers to prevent caching

require_once('../vendor/autoload.php');

// Laminas\Mvc\Application::init() triggers a warning. This seems to be caused
// by inconsistent Container interface usage througout the Laminas code and
// cannot be fixed here. Suppress the warning via a custom error handler - any
// temporary suppression measure won't work.
set_error_handler(
    fn (int $errno, string $errstr) => str_starts_with($errstr, 'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a '),
    E_USER_DEPRECATED
);

\Library\Application::init('Console')->run();
