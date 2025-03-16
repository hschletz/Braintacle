<?php

/**
 * Tests for Model\SoftwareManager
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

namespace Model\Test;

use Braintacle\Direction;
use Braintacle\Software\SoftwareFilter;
use Braintacle\Software\SoftwarePageColumn;

class SoftwareManagerTest extends AbstractTestCase
{
    /** {@inheritdoc} */
    protected static $_tables = [
        'Software',
        'SoftwareDefinitions',
        'SoftwareRaw',
        'WindowsProductKeys',
        'WindowsInstallations',
    ];

    public static function getSoftwareProvider()
    {
        $accepted = array('name' => 'accepted', 'num_clients' => '2');
        $ignored = array('name' => 'ignored', 'num_clients' => '2');
        $new1 = array('name' => 'new1', 'num_clients' => '1');
        $new2 = array('name' => 'new2', 'num_clients' => '2');
        return [
            [
                SoftwareFilter::All,
                SoftwarePageColumn::NumClients,
                Direction::Descending,
                [$accepted, $ignored, $new2, $new1],
            ],
            [SoftwareFilter::All, SoftwarePageColumn::Name, Direction::Ascending, [$accepted, $ignored, $new1, $new2]],
            [SoftwareFilter::Accepted, SoftwarePageColumn::Name, Direction::Ascending, [$accepted]],
            [SoftwareFilter::Ignored, SoftwarePageColumn::Name, Direction::Ascending, [$ignored]],
            [SoftwareFilter::New, SoftwarePageColumn::Name, Direction::Ascending, [$new1, $new2]],
        ];
    }

    /**
     * @dataProvider getSoftwareProvider
     */
    public function testGetSoftware($filter, $order, $direction, $expected)
    {
        $model = $this->getModel();
        $software = $model->getSoftware($filter, $order, $direction);
        $this->assertInstanceOf('Laminas\Db\ResultSet\ResultSet', $software);
        $this->assertEquals($expected, iterator_to_array($software));
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

    public static function setProductKeyProvider()
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
