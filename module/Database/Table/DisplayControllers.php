<?php

/**
 * "videos" table
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

/**
 * "videos" table
 */
class DisplayControllers extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'videos';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'name' => 'Name',
                    'chipset' => 'Chipset',
                    'memory' => 'Memory',
                    'resolution' => 'CurrentResolution',
                )
            )
        );
        // Strategies are only required on hydration. Once sanitized, the
        // original values can be discarded.
        $this->_hydrator->addStrategy(
            'Memory',
            new \Database\Hydrator\Strategy\DisplayControllers\Memory()
        );
        $this->_hydrator->addStrategy(
            'CurrentResolution',
            new \Database\Hydrator\Strategy\DisplayControllers\CurrentResolution()
        );

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Client\Item\DisplayController')
        );

        parent::__construct($serviceLocator);
    }
}
