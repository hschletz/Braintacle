<?php

/**
 * Bootstrap for unit tests
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

namespace Model;

use Braintacle\Database\DatabaseFactory;
use Laminas\Db\Adapter\Adapter;
use Model\Test\AbstractTestCase;
use Nada\Factory;

error_reporting(-1);
date_default_timezone_set('Europe/Berlin');
\Locale::setDefault('de');

$adapter = new Adapter(
    json_decode(
        getenv('BRAINTACLE_TEST_DATABASE'),
        true
    )
);
$databaseFactory = new DatabaseFactory(new Factory(), $adapter);

$serviceManager = \Library\Application::init('Model')->getServiceManager();
$serviceManager->setService('Db', $adapter);
$serviceManager->setService('Database\Nada', $databaseFactory());
AbstractTestCase::$serviceManager = $serviceManager;

unset($serviceManager);
unset($databaseFactory);
unset($adapter);
