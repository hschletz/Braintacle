<?php

/**
 * Tests for DuplicatesController
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

use Console\Form\ShowDuplicates;
use Console\View\Helper\Form\ShowDuplicates as FormShowDuplicates;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for DuplicatesController
 */
class DuplicatesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * @var MockObject|DuplicatesManager
     */
    protected $_duplicates;

    /**
     * @var MockObject|ShowDuplicates
     */
    protected $_showDuplicates;

    public function setUp(): void
    {
        parent::setUp();

        $this->_duplicates = $this->createMock('Model\Client\DuplicatesManager');
        $this->_showDuplicates = $this->createMock('Console\Form\ShowDuplicates');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Client\DuplicatesManager', $this->_duplicates);
        $serviceManager->get('FormElementManager')->setService('Console\Form\ShowDuplicates', $this->_showDuplicates);
    }

    public function testIndexActionNoDuplicates()
    {
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'Keine Duplikate vorhanden.');
    }

    public function testIndexActionShowDuplicates()
    {
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(2));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        // List with 4 hyperlinks.
        $this->assertQueryCount('td a[href*="/console/duplicates/manage/?criteria="]', 4);
        $this->assertQueryContentContains('td a[href*="/console/duplicates/manage/?criteria="]', "\n2\n");
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
        $flashMessenger->method('__call')
                       ->withConsecutive(
                           array('getMessagesFromNamespace', array('error')),
                           array('getMessagesFromNamespace', array('info')),
                           array('getMessagesFromNamespace', array('success'))
                       )->willReturnOnConsecutiveCalls(
                           [],
                           ['info message'],
                           ["success message"]
                       );
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

    public function testManageActionMissingCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->never())->method('render');
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with(null)
                          ->will($this->throwException(new \InvalidArgumentException('Invalid criteria')));
        $this->_duplicates->expects($this->never())->method('merge');

        $this->dispatch('/console/duplicates/manage/');
        $this->assertApplicationException('InvalidArgumentException');
    }

    public function testManageActionInvalidCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->never())->method('render');
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('invalid')
                          ->will($this->throwException(new \InvalidArgumentException('Invalid criteria')));
        $this->_duplicates->expects($this->never())->method('merge');

        $this->dispatch('/console/duplicates/manage/?criteria=invalid');
        $this->assertApplicationException('InvalidArgumentException');
    }

    public function testManageActionGet()
    {
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc')
                          ->willReturn('client_list');
        $this->_duplicates->expects($this->never())->method('merge');
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())
                              ->method('setOptions')
                              ->with(array('clients' => 'client_list', 'order' => 'Id', 'direction' => 'asc'));
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn(array());

        $formHelper = $this->createMock(FormShowDuplicates::class);
        $formHelper->method('__invoke')->with($this->_showDuplicates)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormShowDuplicates', $formHelper);

        $this->dispatch('/console/duplicates/manage/?criteria=Name');
        $this->assertResponseStatusCode(200);
    }

    public function testManageActionPostValid()
    {
        $params = ['key' => 'value'];

        $mergeOptions = ['mergeCustomFields', 'mergePackages'];
        $formData = [
            'clients' => [1, 2],
            'mergeOptions' => $mergeOptions
        ];

        $this->_showDuplicates->expects($this->once())->method('setData')->with($params);
        $this->_showDuplicates->expects($this->once())->method('isValid')->willReturn(true);
        $this->_showDuplicates->method('getData')->willReturn($formData);
        $this->_showDuplicates->expects($this->never())->method('getMessages');

        $this->_duplicates->expects($this->never())->method('find');
        $this->_duplicates->expects($this->once())->method('merge')->with([1, 2], $mergeOptions);

        $formHelper = $this->createMock(FormShowDuplicates::class);
        $formHelper->expects($this->never())->method('__invoke');

        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormShowDuplicates', $formHelper);

        $this->dispatch('/console/duplicates/manage/', 'POST', $params);
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'Die ausgewählten Clients wurden zusammengeführt.',
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testManageActionPostInvalid()
    {
        $this->_showDuplicates->expects($this->once())
                              ->method('setData');
        $this->_showDuplicates->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())
                              ->method('setOptions')
                              ->with(array('clients' => 'client_list', 'order' => 'Id', 'direction' => 'asc'));
        $this->_showDuplicates->expects($this->once())
                              ->method('getMessages')
                              ->willReturn(array('clients' => array('invalid')));
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc')
                          ->willReturn('client_list');
        $this->_duplicates->expects($this->never())
                          ->method('merge');

        $formHelper = $this->createMock(FormShowDuplicates::class);
        $formHelper->method('__invoke')->with($this->_showDuplicates)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormShowDuplicates', $formHelper);

        $this->dispatch('/console/duplicates/manage/?criteria=Name', 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryCount('//ul', 1);
        $this->assertXPathQueryCount('//li', 1);
        $this->assertXPathQueryContentContains('//ul[@class="error"]/li', 'invalid');
        $this->assertXpathQuery('//form');
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
        $this->assertRedirectTo('/console/duplicates/manage/?criteria=Serial');
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
