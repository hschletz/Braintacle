<?php

/**
 * Tests for Model\Package\Package
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

namespace Model\Test\Package;

class PackageTest extends \Model\Test\AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testExchangeArrayWithId()
    {
        $model = $this->getModel();
        $model->exchangeArray(array('Id' => '1425211367'));
        $this->assertEquals(new \DateTime('2015-03-01 13:02:47'), $model->Timestamp);
    }

    public function testExchangeArrayWithoutId()
    {
        $model = $this->getModel();
        $model->exchangeArray(array('Name' => 'name'));
        $this->assertEquals('name', $model->Name);
    }
}
