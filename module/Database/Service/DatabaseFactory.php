<?php

/**
 * Factory for Doctrine DBAL connection
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Database\Connection;
use Database\Event\LoggingEventListener;
use Doctrine\DBAL\DriverManager;
use Interop\Container\ContainerInterface;

/**
 * Factory for Doctrine DBAL connection
 *
 * @codeCoverageIgnore
 */
class DatabaseFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Retreive database configuration from config file.
        $config = $container->get('Library\UserConfig')['database'];
        // Set charset to utf8mb4 for MySQL, utf8 for everything else.
        if (stripos($config['driver'], 'mysql') === false) {
            $config['charset'] = 'utf8';
        } else {
            $config['charset'] = 'utf8mb4';
        }
        $config['wrapperClass'] = Connection::class;

        $logger = $container->get('Library\Logger');
        $eventSubscriber = new LoggingEventListener($logger);

        $connection = DriverManager::getConnection($config);
        $connection->setLogger($logger);
        $connection->getEventManager()->addEventSubscriber($eventSubscriber);

        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                $connection->executeStatement("SET timezone TO 'UTC'");
                break;
            case 'mysql':
                $connection->executeStatement("SET time_zone = '+00:00'");
                break;
            default:
        }

        return $connection;
    }
}
