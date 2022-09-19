<?php

/**
 * Tests for the Clients table
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

namespace Database\Test\Table;

class ClientsTest extends AbstractTest
{
    public static function setUpBeforeClass(): void
    {
        // These tables must exist before the view can be created
        static::$serviceManager->get('Database\Table\ClientsAndGroups')->updateSchema(true);
        static::$serviceManager->get('Database\Table\ClientSystemInfo')->updateSchema(true);
        parent::setUpBeforeClass();
    }

    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testHydrator()
    {
        $hydrator = static::$_table->getHydrator();
        $this->assertInstanceOf('Database\Hydrator\Clients', $hydrator);

        $resultSet = static::$_table->getResultSetPrototype();
        $this->assertInstanceOf('Laminas\Db\ResultSet\HydratingResultSet', $resultSet);
        $this->assertSame($hydrator, $resultSet->getHydrator());
        $this->assertInstanceOf('Model\Client\Client', $resultSet->getObjectPrototype());
    }
}
