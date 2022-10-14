<?php

/**
 * Tests for LicensesController
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

namespace Console\Test\Controller;

use Model\SoftwareManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for LicensesController
 */
class LicensesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Software manager mock
     * @var MockObject|SoftwareManager
     */
    protected $_softwareManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->_softwareManager = $this->createMock('Model\SoftwareManager');
        $this->getApplicationServiceLocator()->setService('Model\SoftwareManager', $this->_softwareManager);
    }

    public function testIndexActionNoManualKeys()
    {
        $this->_softwareManager->expects($this->once())->method('getNumManualProductKeys')->willReturn(0);

        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'Manuell eingegebene Windows-Lizenzschlüssel: 0');
    }

    public function testIndexActionManualKeys()
    {
        $this->_softwareManager->expects($this->once())->method('getNumManualProductKeys')->willReturn(1);

        $this->dispatch('/console/licenses/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryContentContains(
            '//p/a[@href="/console/client/index/?' .
            'columns=Name,OsName,Windows.ProductKey,Windows.ManualProductKey&' .
            'filter=Windows.ManualProductKey&order=Name&direction=asc"]',
            "\n1\n"
        );
    }
}
