<?php

/**
 * Tests for DuplicatesController
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

namespace Console\Test\Controller;

use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Console\Test\AbstractControllerTestCase;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for DuplicatesController
 */
class DuplicatesControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockObject|DuplicatesManager
     */
    protected $_duplicates;

    private MockObject $flashMessages;

    public function setUp(): void
    {
        parent::setUp();

        $this->_duplicates = $this->createMock('Model\Client\DuplicatesManager');

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->willReturnCallback(fn ($name, $arguments) => $name . json_encode($arguments));

        $this->flashMessages = $this->createMock(FlashMessages::class);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(RouteHelper::class, $routeHelper);
        $serviceManager->setService(FlashMessages::class, $this->flashMessages);
        $serviceManager->setService('Model\Client\DuplicatesManager', $this->_duplicates);
    }

    public function testIndexActionNoDuplicates()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn([]);
        $this->_duplicates->expects($this->exactly(4))
            ->method('count')
            ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'Keine Duplikate vorhanden.');
    }

    public function testIndexActionShowDuplicates()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn([]);
        $this->_duplicates->expects($this->exactly(4))
            ->method('count')
            ->will($this->returnValue(2));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        // List with 4 hyperlinks.
        $this->assertQueryCount('td a[href*="manageDuplicates{\"criterion\":"]', 4);
        $this->assertQueryContentContains('td a[href*="manageDuplicates{\"criterion\":"]', "\n2\n");
    }

    public function testIndexActionNoMessage()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn([]);
        $this->_duplicates->method('count')->willReturn(0);
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="success"]');
    }

    public function testIndexActionMessageMerged()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn(['success message']);
        $this->_duplicates->method('count')->willReturn(0);
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[@class="success"][normalize-space(text())="success message"]');
    }
}
