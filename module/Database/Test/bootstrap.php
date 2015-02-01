<?php
/**
 * Bootstrap for unit tests
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
 */

error_reporting(-1);
require_once('Nada.php');
require_once(__DIR__ . '/../../Library/Application.php');
\Library\Application::init('Database', false);

// Replace global ZF1 adapter with SQLite :memory: database.
$zf1adapter = new Zend_Db_Adapter_Pdo_Sqlite(array('dbname' => ':memory:'));
Zend_Registry::set('db', $zf1adapter);

// Replace global ZF2 adapter with SQLite :memory: database.
// This must be shared with the ZF1 adapter.
$adapter = new \Zend\Db\Adapter\Adapter(
    array(
        'driver' => 'Pdo_Sqlite',
    )
);
$adapter->getDriver()->getConnection()->setResource($zf1adapter->getConnection());
$serviceManager = \Library\Application::getService('ServiceManager');
$serviceManager->setAllowOverride(true);
$serviceManager->setService('Db', $adapter);

// Unset temporary variables to prevent PHPUnit from backing them up which may
// cause errors.
unset($zf1adapter);
unset($adapter);
unset($serviceManager);
