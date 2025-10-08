<?php

/**
 * "groups" table
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Table;

use Laminas\Hydrator\ObjectPropertyHydrator;
use Model\Config as ConfigModel;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;

/**
 * "groups" table
 */
class GroupInfo extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'groups';
        // Hydrator and ResultSet initialization is postponed to initialize()
        // because they depend on reading from Model\Config which is
        // inappropriate in a constructor and may not be functional under
        // certain circumstances (database initialization)
        parent::__construct($container);
    }

    /** {@inheritdoc} */
    public function initialize()
    {
        $this->_hydrator = new ObjectPropertyHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'id' => 'id',
                    'name' => 'name',
                    'description' => 'description',
                    'lastdate' => 'creationDate',
                    'request' => 'dynamicMembersSql',
                    'create_time' => 'cacheCreationDate',
                    'revalidate_from' => 'cacheExpirationDate',
                )
            )
        );

        $dateTimeFormatter = new \Laminas\Hydrator\Strategy\DateTimeFormatterStrategy(
            $this->container->get(AbstractDatabase::class)->timestampFormatPhp()
        );
        $this->_hydrator->addStrategy('creationDate', $dateTimeFormatter);
        $this->_hydrator->addStrategy('lastdate', $dateTimeFormatter);

        $cacheCreationDateStrategy = new \Database\Hydrator\Strategy\Groups\CacheDate();
        $this->_hydrator->addStrategy('cacheCreationDate', $cacheCreationDateStrategy);
        $this->_hydrator->addStrategy('create_time', $cacheCreationDateStrategy);

        $cacheExpirationDateStrategy = new \Database\Hydrator\Strategy\Groups\CacheDate(
            $this->container->get(ConfigModel::class)->groupCacheExpirationInterval
        );
        $this->_hydrator->addStrategy('cacheExpirationDate', $cacheExpirationDateStrategy);
        $this->_hydrator->addStrategy('revalidate_from', $cacheExpirationDateStrategy);

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $this->container->get(Group::class)
        );

        parent::initialize();

        return; // satisfy parent return type declaration
    }
}
