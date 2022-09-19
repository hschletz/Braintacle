<?php

/**
 * Abstract factory for database adapter and NADA interface
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

namespace Database\Service;

/**
 * Abstract factory for database adapter and NADA interface
 *
 * There is a mutual dependency between the "Db" and "Database\Nada" services.
 * The connection is initialized with NADA's setTimezone() method, which relies
 * on a configured adapter.
 *
 * Since circular dependencies are not supported, this abstract factory creates
 * both services and performs all necessary initializations.
 */
class AbstractDatabaseFactory implements \Laminas\ServiceManager\Factory\AbstractFactoryInterface
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        return $requestedName == 'Db' or $requestedName == 'Database\Nada';
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        // Retreive database configuration from config file.
        $config = $container->get('Library\UserConfig')['database'];
        $config['options']['buffer_results'] = true;
        // Set charset to utf8mb4 for MySQL, utf8 for everything else.
        if (stripos($config['driver'], 'mysql') === false) {
            $config['charset'] = 'utf8';
        } else {
            $config['charset'] = 'utf8mb4';
        }

        $adapter = new \Laminas\Db\Adapter\Adapter($config);
        $database = \Nada\Factory::getDatabase($adapter);

        if ($database->isSqlite()) {
            $database->emulatedDatatypes = array('bool', 'date', 'decimal', 'timestamp');
        } elseif ($database->isMySql()) {
            $database->emulatedDatatypes = array('bool');
        }
        $database->setTimezone();

        // Return requested service, store instance of other service first
        if ($requestedName == 'Db') {
            $container->setService('Database\Nada', $database);
            return $adapter;
        } else {
            $container->setService('Db', $adapter);
            return $database;
        }
    }
}
