<?php
/**
 * Tests for LicensesController
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

namespace Console\Test\Controller;

/**
 * Tests for LicensesController
 */
class LicensesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Software manager mock
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\LicensesController($this->_softwareManager);
    }

    public function testIndexActionNoManualKeys()
    {
        // Zero manual product keys produce <dd>0</dd>.
        $this->_softwareManager = $this->getMockBuilder('Model\SoftwareManager')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->_softwareManager->expects($this->once())->method('getNumManualProductKeys')->willReturn(0);

        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('dd', "\n0\n");
    }

    public function testIndexActionManualKeys()
    {
        // Nonzero manual product keys produce <dd><a...>n</a></dd>.
        $this->_softwareManager = $this->getMockBuilder('Model\SoftwareManager')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->_softwareManager->expects($this->once())->method('getNumManualProductKeys')->willReturn(1);

        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryContentContains(
            '//dd/a[@href="/console/client/index/?' .
            'columns=Name,OsName,Windows.ProductKey,Windows.ManualProductKey&' .
            'filter=Windows.ManualProductKey&order=Name&direction=asc"]',
            "\n1\n"
        );
    }
}
