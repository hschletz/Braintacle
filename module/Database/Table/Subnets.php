<?php

/**
 * "subnet" table
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
 * "subnet" table
 */
class Subnets extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'subnet';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'netid' => 'Address',
                    'mask' => 'Mask',
                    'name' => 'Name',
                    'num_inventoried' => 'NumInventoried',
                    'num_identified' => 'NumIdentified',
                    'num_unknown' => 'NumUnknown',
                )
            )
        );
        // Strategies are only required on hydration.
        $integerStrategy = new \Library\Hydrator\Strategy\Integer();
        $this->_hydrator->addStrategy('NumInventoried', $integerStrategy);
        $this->_hydrator->addStrategy('NumIdentified', $integerStrategy);
        $this->_hydrator->addStrategy('NumUnknown', $integerStrategy);

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Network\Subnet')
        );

        parent::__construct($serviceLocator);
    }
}
