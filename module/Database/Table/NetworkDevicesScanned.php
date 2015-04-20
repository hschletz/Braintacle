<?php
/**
 * "netmap" table
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

Namespace Database\Table;

/**
 * "netmap" table
 */
class NetworkDevicesScanned extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'netmap';

        $this->_hydrator = new \Zend\Stdlib\Hydrator\ArraySerializable;
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'ip' => 'IpAddress',
                    'mac' => 'MacAddress',
                    'name' => 'Hostname',
                    'date' => 'DiscoveryDate',
                    'description' => 'Description',
                    'type' => 'Type',
                )
            )
        );

        $zendDate = new \Library\Hydrator\Strategy\ZendDate;
        $this->_hydrator->addStrategy('DiscoveryDate', $zendDate);
        $this->_hydrator->addStrategy('date', $zendDate);

        $macAddress = new \Library\Hydrator\Strategy\MacAddress;
        $this->_hydrator->addStrategy('MacAddress', $macAddress);
        $this->_hydrator->addStrategy('mac', $macAddress);

        $this->resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet(
            $this->_hydrator, $serviceLocator->get('Model\Network\Device')
        );
        parent::__construct($serviceLocator);
    }
}
