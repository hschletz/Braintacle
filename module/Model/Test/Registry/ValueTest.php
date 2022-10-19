<?php

/**
 * Tests for Model\Registry\Value
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

namespace Model\Test\Registry;

class ValueTest extends \Model\Test\AbstractTest
{
    public function getDataSet()
    {
        return new \PHPUnit\DbUnit\DataSet\DefaultDataSet();
    }

    public function testObjectProperties()
    {
        $model = $this->getModel();
        $this->assertInstanceOf('ArrayAccess', $model);
        $this->assertTrue(method_exists($model, 'exchangeArray'));
    }

    public function testFullPathPropertyPropertyExplicitValue()
    {
        $model = $this->getModel();
        $model->RootKey = \Model\Registry\Value::HKEY_LOCAL_MACHINE;
        $model->SubKeys = 'a\b';
        $model->Value = 'configured';
        $this->assertEquals('HKEY_LOCAL_MACHINE\a\b\configured', $model->fullPath);
    }

    public function testFullPathPropertyPropertyAllValues()
    {
        $model = $this->getModel();
        $model->RootKey = \Model\Registry\Value::HKEY_LOCAL_MACHINE;
        $model->SubKeys = 'a\b';
        $model->Value = null;
        $this->assertEquals('HKEY_LOCAL_MACHINE\a\b\*', $model->fullPath);
    }

    public function testRootKeys()
    {
        $rootKeys = array(
            \Model\Registry\Value::HKEY_CLASSES_ROOT => 'HKEY_CLASSES_ROOT',
            \Model\Registry\Value::HKEY_CURRENT_USER => 'HKEY_CURRENT_USER',
            \Model\Registry\Value::HKEY_LOCAL_MACHINE => 'HKEY_LOCAL_MACHINE',
            \Model\Registry\Value::HKEY_USERS => 'HKEY_USERS',
            \Model\Registry\Value::HKEY_CURRENT_CONFIG => 'HKEY_CURRENT_CONFIG',
            \Model\Registry\Value::HKEY_DYN_DATA => 'HKEY_DYN_DATA',
        );
        $model = $this->getModel();
        $this->assertEquals($rootKeys, $model->rootKeys());
    }
}
