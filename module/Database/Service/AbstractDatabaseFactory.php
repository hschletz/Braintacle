<?php
/**
 * Abstract factory for database adapter and NADA interface
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

namespace Database\Service;

/**
 * Abstract factory for database adapter and NADA interface
 *
 * There is a mutual dependency between the "Db" and "Database\Nada" services.
 * The connection is initialized with NADA's setTimezone() method, which relies
 * on a configured adapter.
 *
 * Since circular dependencies are not supported, this abstract factory creates
 * both services if they don't already exist and performs all necessary
 * initializations.
 */
class AbstractDatabaseFactory implements \Zend\ServiceManager\AbstractFactoryInterface
{
    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function canCreateServiceWithName(
        \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator,
        $name,
        $requestedName
    ) {
        return $requestedName == 'Db' or $requestedName == 'Database\Nada';
    }

    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function createServiceWithName(
        \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator,
        $name,
        $requestedName
    ) {
        // Create or retrieve adapter
        if ($serviceLocator->has('Db', false)) {
            $adapter = $serviceLocator->get('Db');
        } else {
            // Retreive database configuration from config file.
            $config = $serviceLocator->get('Library\UserConfig')['database'];
            $config['options']['buffer_results'] = true;
            // Set charset to utf8mb4 for MySQL, utf8 for everything else.
            if (stripos($config['driver'], 'mysql') === false) {
                $config['charset'] = 'utf8';
            } else {
                $config['charset'] = 'utf8mb4';
            }

            $adapter = new \Zend\Db\Adapter\Adapter($config);

            // Create service unless it was explicitly requested, in which case
            // the servive manager will store it later.
            if ($requestedName != 'Db') {
                $serviceLocator->setService('Db', $adapter);
            }
        }

        // Create or retrieve NADA interface
        if ($serviceLocator->has('Database\Nada', false)) {
            $database = $serviceLocator->get('Database\Nada');
        } else {
            $database = \Nada\Factory::getDatabase($adapter);

            if ($database->isSqlite()) {
                $database->emulatedDatatypes = array('bool', 'date', 'decimal', 'timestamp');
            } elseif ($database->isMySql()) {
                $database->emulatedDatatypes = array('bool');
            }
            $database->setTimezone();

            // Create service unless it was explicitly requested, in which case
            // the servive manager will store it later.
            if ($requestedName != 'Database\Nada') {
                $serviceLocator->setService('Database\Nada', $database);
            }
        }

        // Return requested service
        return ($requestedName == 'Db') ? $adapter : $database;
    }
}
