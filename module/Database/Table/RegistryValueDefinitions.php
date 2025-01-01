<?php

/**
 * "regconfig" table
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

use Model\Registry\Value;
use Psr\Container\ContainerInterface;

/**
 * "regconfig" table
 */
class RegistryValueDefinitions extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(ContainerInterface $container)
    {
        $this->table = 'regconfig';

        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy(
                array(
                    'id' => 'Id',
                    'name' => 'Name',
                    'regtree' => 'RootKey',
                    'regkey' => 'SubKeys',
                    'regvalue' => 'Value',
                )
            )
        );

        $value = new \Database\Hydrator\Strategy\RegistryValueDefinitions\Value();
        $this->_hydrator->addStrategy('Value', $value);
        $this->_hydrator->addStrategy('regvalue', $value);

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $container->get(Value::class)
        );
        parent::__construct($container);
    }
}
