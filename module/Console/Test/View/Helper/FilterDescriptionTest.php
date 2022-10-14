<?php

/**
 * Tests for the FilterDescription helper
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

namespace Console\Test\View\Helper;

use Model\Network\Subnet;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for the FilterDescription helper
 */
class FilterDescriptionTest extends \Library\Test\View\Helper\AbstractTest
{
    public function testInterfaceInSubnet()
    {
        /** @var MockObject|Subnet */
        $subnet = $this->createMock(Subnet::class);
        $subnet->expects($this->exactly(2))
               ->method('offsetSet')
               ->withConsecutive(['Address', 'address1'], ['Mask', 'mask1']);
        $subnet->method('offsetGet')->with('CidrAddress')->willReturn('<cidrAddress1>');

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
            $this->getHelper()('PackagePending', '>Name', 42)
        );
    }

    public function testPackageRunning()
    {
        $this->assertEquals(
            "42 Clients mit laufender Installation von Paket &#039;&gt;Name&#039;",
            $this->getHelper()('PackageRunning', '>Name', 42)
        );
    }

    public function testPackageSuccess()
    {
        $this->assertEquals(
            "42 Clients mit erfolgreich installiertem Paket &#039;&gt;Name&#039;",
            $this->getHelper()('PackageSuccess', '>Name', 42)
        );
    }

    public function testPackageError()
    {
        $this->assertEquals(
            "42 Clients, bei denen die Installation von Paket &#039;&gt;Name&#039; fehlgeschlagen ist",
            $this->getHelper()('PackageError', '>Name', 42)
        );
    }

    public function testSoftware()
    {
        $this->assertEquals(
            "42 Clients, auf denen die Software &#039;&gt;Name&#039; installiert ist",
            $this->getHelper()('Software', '>Name', 42)
        );
    }

    public function testManualProductKey()
    {
        $this->assertEquals(
            '42 Clients mit manuell eingegebenem Windows-Lizenzschlüssel',
            $this->getHelper()('Windows.ManualProductKey', 'dummy', 42)
        );
    }

    public function testInvalidArrayFilter()
    {
        $this->expectException(
            'InvalidArgumentException',
            'No description available for this set of multiple filters'
        );
        $this->getHelper()(array('NetworkInterface.Subnet'), null, 42);
    }

    public function testInvalidStringFilter()
    {
        $this->expectException(
            'InvalidArgumentException',
            'No description available for filter invalid'
        );
        $this->getHelper()('invalid', null, 42);
    }
}
