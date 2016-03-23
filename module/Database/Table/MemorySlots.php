<?php
/**
 * "memories" table
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
 * "memories" table
 */
class MemorySlots extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'memories';

        $this->_hydrator = new \Zend\Hydrator\ArraySerializable;
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'numslots' => 'SlotNumber',
                    'type' => 'Type',
                    'capacity' => 'Size',
                    'speed' => 'Clock',
                    'caption' => 'Caption',
                    'description' => 'Description',
                    'serialnumber' => 'Serial',
                )
            )
        );
        $this->_hydrator->addStrategy('Size', new \Database\Hydrator\Strategy\MemorySlots\Size);
        $this->_hydrator->addStrategy('capacity', new \Database\Hydrator\Strategy\MemorySlots\Size);
        $this->_hydrator->addStrategy('Clock', new \Database\Hydrator\Strategy\MemorySlots\Clock);
        $this->_hydrator->addStrategy('speed', new \Database\Hydrator\Strategy\MemorySlots\Clock);

        $this->resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Client\Item\MemorySlot')
        );

        parent::__construct($serviceLocator);
    }
}
