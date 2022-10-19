<?php

/**
 * Tests for ClientsBios hydrator
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

namespace Protocol\Test\Hydrator;

use Model\AbstractModel;

class ClientsBiosTest extends \Library\Test\Hydrator\AbstractHydratorTest
{
    protected $_extracted = array(
        'ASSETTAG' => 'asset tag',
        'BDATE' => 'bios date',
        'BMANUFACTURER' => 'bios manufacturer',
        'BVERSION' => 'bios version',
        'SMANUFACTURER' => 'manufacturer',
        'SMODEL' => 'model',
        'SSN' => 'serial',
        'TYPE' => 'type',
    );

    protected $_hydrated = [
        'assetTag' => 'asset tag',
        'biosDate' => 'bios date',
        'biosManufacturer' => 'bios manufacturer',
        'biosVersion' => 'bios version',
        'manufacturer' => 'manufacturer',
        'model' => 'model',
        'serial' => 'serial',
        'type' => 'type',
        'idString' => 'ignored',
    ];

    public function hydrateProvider()
    {
        return array(array($this->_extracted + array('IGNORED' => 'ignored'), $this->_hydrated));
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrateWithStdClass(array $data, array $objectData)
    {
        $hydrator = $this->getHydrator();
        $object = (object) $objectData;
        $object->idString = 'ignored';
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $this->assertEquals($objectData, get_object_vars($object));
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrateWithAbstractModel(array $data, array $objectData)
    {
        $hydrator = $this->getHydrator();
        $object = $this->getMockForAbstractClass(AbstractModel::class);
        $object->idString = 'ignored';
        $this->assertSame($object, $hydrator->hydrate($data, $object));
        $expected = [];
        foreach ($objectData as $key => $value) {
            $expected[ucfirst($key)] = $value;
        }
        $this->assertEquals($expected, $object->getArrayCopy());
    }

    public function extractProvider()
    {
        return array(array($this->_hydrated, $this->_extracted));
    }
}
