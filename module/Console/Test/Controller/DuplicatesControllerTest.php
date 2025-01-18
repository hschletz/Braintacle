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
use Console\Form\ShowDuplicates;
use Console\Test\AbstractControllerTestCase;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
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

    /**
     * @var MockObject|ShowDuplicates
     */
    protected $_showDuplicates;

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

    public function testIndexActionNoFlashMessages()
    {
        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->method('__invoke')->with(null)->willReturnSelf();
        $flashMessenger->method('__call')
            ->with('getMessagesFromNamespace')
            ->willReturn(array());
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('FlashMessenger', $flashMessenger);

        $this->_duplicates->method('count')
            ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//ul');
    }

    public function testIndexActionRenderFlashMessages()
    {
        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->method('__invoke')->with(null)->willReturnSelf();
        $flashMessenger->method('__call')->willReturnMap([
            ['getMessagesFromNamespace', ['error'], []],
            ['getMessagesFromNamespace', ['info'], ['info message']],
            ['getMessagesFromNamespace', ['success'], ['success message']]
        ]);
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('flashMessenger', $flashMessenger);

        $this->_duplicates->method('count')
            ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//ul', 2);
        $this->assertXPathQueryContentContains(
            '//ul[@class="info"]/li',
            'info message'
        );
        $this->assertXPathQueryContentContains(
            '//ul[@class="success"]/li',
            "success message"
        );
    }

    public function testIndexActionNoMessage()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn([]);
        $this->_duplicates->method('count')->willReturn(0);
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="success"]');
    }

    public function testIndexActionMessage()
    {
        $this->flashMessages->method('get')->with(FlashMessages::Success)->willReturn(['success message']);
        $this->_duplicates->method('count')->willReturn(0);
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[@class="success"][normalize-space(text())="success message"]');
    }

    public function testAllowActionGet()
    {
        $this->_duplicates->expects($this->never())
            ->method('allow');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testAllowActionPostNo()
    {
        $this->_duplicates->expects($this->never())
            ->method('allow');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/duplicates/index/');
    }

    public function testAllowActionPostYes()
    {
        $this->_duplicates->expects($this->once())
            ->method('allow')
            ->with('Serial', '12345678');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertEquals(
            ["'12345678' wird nicht mehr als Duplikat betrachtet."],
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }
}
