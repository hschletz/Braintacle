<?php

/**
 * Tests for Model\SoftwareManager
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

namespace Model\Test;

class SoftwareManagerTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = [
        'ClientsAndGroups',
        'Software',
        'SoftwareDefinitions',
        'SoftwareRaw',
        'WindowsProductKeys',
        'WindowsInstallations',
    ];

    public function getSoftwareProvider()
    {
        $accepted = array('name' => 'accepted', 'num_clients' => '2');
        $acceptedOs = array('name' => 'accepted', 'num_clients' => '1');
        $ignored = array('name' => 'ignored', 'num_clients' => '2');
        $ignoredOs = array('name' => 'ignored', 'num_clients' => '1');
        $new1 = array('name' => 'new1', 'num_clients' => '1');
        $new2 = array('name' => 'new2', 'num_clients' => '2');
        $new2Os = array('name' => 'new2', 'num_clients' => '1');
        return array(
            array(null, 'name', 'asc', array($accepted, $ignored, $new1, $new2)),
            array(array(), 'num_clients', 'desc', array($accepted, $ignored, $new2, $new1)),
            array(array('Os' => 'windows'), 'name', 'asc', array($acceptedOs, $ignoredOs, $new1, $new2Os)),
            array(array('Os' => 'other'), 'name', 'asc', array($acceptedOs, $ignoredOs, $new2Os)),
            array(array('Status' => 'all'), 'name', 'asc', array($accepted, $ignored, $new1, $new2)),
            array(array('Status' => 'accepted'), 'name', 'asc', array($accepted)),
            array(array('Status' => 'ignored'), 'name', 'asc', array($ignored)),
            array(array('Status' => 'new'), 'name', 'asc', array($new1, $new2)),
        );
    }

    /**
     * @dataProvider getSoftwareProvider
     */
    public function testGetSoftware($filters, $order, $direction, $expected)
    {
        $model = $this->getModel();
        $software = $model->getSoftware($filters, $order, $direction);
        $this->assertInstanceOf('Laminas\Db\ResultSet\ResultSet', $software);
        $this->assertEquals($expected, iterator_to_array($software));
    }

    public function getSoftwareInvalidArgumentsProvider()
    {
        return array(
            array(array('Os' => 'invalid'), 'name', 'Invalid OS filter: invalid'),
            array(array('Status' => 'invalid'), 'name', 'Invalid status filter: invalid'),
            array(array('invalid' => ''), 'name', 'Invalid filter: invalid'),
            array(null, 'invalid', 'Invalid order column: invalid'),
        );
    }

    /**
     * @dataProvider getSoftwareInvalidArgumentsProvider
     */
    public function testGetSoftwareInvalidArgument($filters, $order, $message)
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage($message);
        $model = $this->getModel();
        $software = $model->getSoftware($filters, $order);
    }

    public function testSetDisplay()
    {
        $model = $this->getModel();
        $model->setDisplay('new1', true); // updated
        $model->setDisplay('new2', true);
        $model->setDisplay('new3', false);
        $model->setDisplay('new4', false);

        $dataSet = $this->getBooleanDataSetWrapper($this->loadDataSet('SetDisplay'), 0, 1);
        $this->assertTablesEqual(
            $dataSet->getTable('software_definitions'),
            $this->getConnection()->createQueryTable(
                'software_definitions',
                'SELECT name, display FROM software_definitions ORDER BY name'
            )
        );
    }

    public function testGetNumManualProductKeys()
    {
        $model = $this->getModel();
        $this->assertEquals(2, $model->getNumManualProductKeys());
    }

    public function setProductKeyProvider()
    {
        return array(
            array(1, null, 'Empty'),
            array(1, '', 'Empty'),
            array(1, 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY', 'Empty'),
            array(1, 'DEFGH-IJKLM-NOPQR-STUVW-XYZAB', 'Update'),
            array(4, 'DEFGH-IJKLM-NOPQR-STUVW-XYZAB', 'Insert'),
        );
    }

    /**
     * @dataProvider setProductKeyProvider
     */
    public function testSetProductKey($clientId, $productKey, $dataSet)
    {
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will(
            $this->returnValueMap(
                array(
                    array('Id', $clientId),
                    array('Windows', array('ProductKey' => 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY')),
                )
            )
        );
        $model = $this->getModel();
        $model->setProductKey($client, $productKey);
        $this->assertTablesEqual(
            $this->loadDataSet($dataSet)->getTable('braintacle_windows'),
            $this->getConnection()->createQueryTable(
                'braintacle_windows',
                'SELECT hardware_id, manual_product_key FROM braintacle_windows ORDER BY hardware_id'
            )
        );
    }

    public function testSetProductKeyInvalid()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->willReturn(array('ProductKey' => 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY'));

        $model = $this->getModel();
        try {
            $model->setProductKey($client, 'invalid');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("'invalid' is not a valid product key", $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('braintacle_windows'),
            $this->getConnection()->createQueryTable(
                'braintacle_windows',
                'SELECT hardware_id, manual_product_key FROM braintacle_windows'
            )
        );
    }
}
