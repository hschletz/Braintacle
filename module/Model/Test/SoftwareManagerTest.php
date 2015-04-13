<?php
/**
 * Tests for Model\SoftwareManager
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

namespace Model\Test;

class SoftwareManagerTest extends AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('WindowsInstallations');

    public function testGetNumManualProductKeys()
    {
        $model = $this->_getModel();
        $this->assertEquals(2, $model->getNumManualProductKeys());
    }

    public function setProductKeyProvider()
    {
        return array(
            array(1, null, 'Empty'),
            array(1, '', 'Empty'),
            array(1, 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY', 'Empty'),
            array(1, 'DEFGH-IJKLM-NOPQR-STUVW-XYZAB', 'Update'),
            array(3, 'DEFGH-IJKLM-NOPQR-STUVW-XYZAB', 'Insert'),
        );
    }

    /**
     * @dataProvider setProductKeyProvider
     */
    public function testSetProductKey($clientId, $productKey, $dataSet)
    {
        $client = $this->getMock('Model_Computer');
        $client->method('offsetGet')->will(
            $this->returnValueMap(
                array(
                    array('Id', $clientId),
                    array('Windows', array('ProductKey' => 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY')),
                )
            )
        );
        $model = $this->_getModel();
        $model->setProductKey($client, $productKey);
        $this->assertTablesEqual(
            $this->_loadDataSet($dataSet)->getTable('braintacle_windows'),
            $this->getConnection()->createQueryTable(
                'braintacle_windows', 'SELECT hardware_id, manual_product_key FROM braintacle_windows'
            )
        );
    }

    public function testSetProductKeyInvalid()
    {
        $client = $this->getMock('Model_Computer');
        $client->method('offsetGet')->willReturn(array('ProductKey' => 'ABCDE-FGHIJ-KLMNO-PQRST-UVWXY'));

        $model = $this->_getModel();
        try {
            $model->setProductKey($client, 'invalid');
            $this->fail('Expected exception was not thrown');
        } catch (\InvalidArgumentException $e) {
            $this->assertEquals("'invalid' ist kein gültiger Lizenzschlüssel", $e->getMessage());
        }
        $this->assertTablesEqual(
            $this->_loadDataSet()->getTable('braintacle_windows'),
            $this->getConnection()->createQueryTable(
                'braintacle_windows', 'SELECT hardware_id, manual_product_key FROM braintacle_windows'
            )
        );
    }
}
