<?php
/**
 * Tests for the FilterDescription helper
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\View\Helper;

/**
 * Tests for the FilterDescription helper
 */
class FilterDescriptionTest extends \Library\Test\View\Helper\AbstractTest
{
    public function testInterfaceInSubnet()
    {
        $subnet = $this->createMock('Model\Network\Subnet');
        $subnet->expects($this->at(0))->method('offsetSet')->with('Address', 'address1');
        $subnet->expects($this->at(1))->method('offsetSet')->with('Mask', 'mask1');
        $subnet->expects($this->at(2))->method('offsetGet')->with('CidrAddress')->willReturn('<cidrAddress1>');

        $helper = new \Console\View\Helper\FilterDescription($subnet);
        $helper->setView(static::$serviceManager->get('Laminas\View\Renderer\PhpRenderer'));

        // Escaped characters should not occur, but are theoretically possible.
        $this->assertEquals(
            '42 Clients mit Interface in Netzwerk &lt;cidrAddress1&gt;',
            $helper(
                ['NetworkInterface.Subnet', 'NetworkInterface.Netmask'],
                ['address1', 'mask1'],
                42
            )
        );
    }

    public function testPackagePending()
    {
        $this->assertEquals(
            "42 Clients, die auf Installation von Paket &#039;&gt;Name&#039; warten",
            $this->_getHelper()->__invoke('PackagePending', '>Name', 42)
        );
    }

    public function testPackageRunning()
    {
        $this->assertEquals(
            "42 Clients mit laufender Installation von Paket &#039;&gt;Name&#039;",
            $this->_getHelper()->__invoke('PackageRunning', '>Name', 42)
        );
    }

    public function testPackageSuccess()
    {
        $this->assertEquals(
            "42 Clients mit erfolgreich installiertem Paket &#039;&gt;Name&#039;",
            $this->_getHelper()->__invoke('PackageSuccess', '>Name', 42)
        );
    }

    public function testPackageError()
    {
        $this->assertEquals(
            "42 Clients, bei denen die Installation von Paket &#039;&gt;Name&#039; fehlgeschlagen ist",
            $this->_getHelper()->__invoke('PackageError', '>Name', 42)
        );
    }

    public function testSoftware()
    {
        $this->assertEquals(
            "42 Clients, auf denen die Software &#039;&gt;Name&#039; installiert ist",
            $this->_getHelper()->__invoke('Software', '>Name', 42)
        );
    }

    public function testManualProductKey()
    {
        $this->assertEquals(
            '42 Clients mit manuell eingegebenem Windows-Lizenzschlüssel',
            $this->_getHelper()->__invoke('Windows.ManualProductKey', 'dummy', 42)
        );
    }

    public function testInvalidArrayFilter()
    {
        $this->expectException(
            'InvalidArgumentException',
            'No description available for this set of multiple filters'
        );
        $this->_getHelper()->__invoke(array('NetworkInterface.Subnet'), null, 42);
    }

    public function testInvalidStringFilter()
    {
        $this->expectException(
            'InvalidArgumentException',
            'No description available for filter invalid'
        );
        $this->_getHelper()->__invoke('invalid', null, 42);
    }
}
