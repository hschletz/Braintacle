<?php

/**
 * Bootstrap for unit tests
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Model;

error_reporting(-1);
date_default_timezone_set('Europe/Berlin');
\Locale::setDefault('de');

$serviceManager = \Library\Application::init('Model')->getServiceManager();
$serviceManager->setService(
    'Library\UserConfig',
    array(
        'database' => json_decode(getenv('BRAINTACLE_TEST_DATABASE'), true),
    )
);
\Model\Test\AbstractTest::$serviceManager = $serviceManager;
unset($serviceManager);
