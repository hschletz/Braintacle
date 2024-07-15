<?php

/**
 * Tests for DuplicatesController
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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
use Console\Test\AbstractControllerTestCase;
use DateTime;
use Exception;
use Laminas\Form\Element\Csrf;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Model\Client\Client;
use Model\Client\DuplicatesManager;
use Model\Config;
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

    /**
     * @var MockObject|Config
     */
    private $config;

    public function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->_duplicates = $this->createMock('Model\Client\DuplicatesManager');
        $this->_showDuplicates = $this->createMock('Console\Form\ShowDuplicates');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService(Config::class, $this->config);
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

    public function testManageActionMissingCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');

        $this->_duplicates->expects($this->once())
            ->method('find')
            ->with(null)
            ->willThrowException(new Exception('Invalid criteria'));
        $this->_duplicates->expects($this->never())->method('merge');

        $this->expectExceptionMessage('Invalid criteria');
        $this->dispatch('/console/duplicates/manage/');
    }

    public function testManageActionInvalidCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');

        $this->_duplicates->expects($this->once())
            ->method('find')
            ->with('invalid')
            ->willThrowException(new Exception('Invalid criteria'));
        $this->_duplicates->expects($this->never())->method('merge');

        $this->expectExceptionMessage('Invalid criteria');
        $this->dispatch('/console/duplicates/manage/?criteria=invalid');
    }

    public function testManageActionGet()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn([]);

        $this->_duplicates->expects($this->once())
            ->method('find')
            ->with('Name', 'Id', 'asc')->willReturn([]);
        $this->_duplicates->expects($this->never())->method('merge');

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

        $this->dispatch('/console/duplicates/manage/', 'POST', $params);
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'Die ausgewählten Clients wurden zusammengeführt.',
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testManageActionPostInvalid()
    {
        $this->_showDuplicates->expects($this->once())->method('setData');
        $this->_showDuplicates->expects($this->once())->method('isValid')->willReturn(false);
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn(['clients' => ['invalid']]);

        $this->_duplicates->expects($this->once())
            ->method('find')
            ->with('Name', 'Id', 'asc')
            ->willReturn([]);
        $this->_duplicates->expects($this->never())
            ->method('merge');

        $this->dispatch('/console/duplicates/manage/?criteria=Name', 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryCount('//ul', 1);
        $this->assertXPathQueryCount('//li', 1);
        $this->assertXPathQueryContentContains('//ul[@class="error"]/li', 'invalid');
        $this->assertXpathQuery('//form');
    }

    public function testManageActionTemplateNoMessages()
    {
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn([]);
        $this->_duplicates->method('find')->willReturn([]);
        $this->dispatch('/console/duplicates/manage/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//ul');
    }

    public function testManageActionTemplateMessages()
    {
        $messages = [
            'element1' => [
                'validator' => 'message1',
            ],
            'element2' => [
                'validator' => '<message2>',
            ],
        ];
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn($messages);
        $this->_duplicates->method('find')->willReturn([]);
        $this->dispatch('/console/duplicates/manage/');
        $this->assertXpathQueryCount('//li', 2);
        $this->assertXpathQueryContentContains('//li[1]', 'message1');
        $this->assertXpathQueryContentContains('//li[2]', '<message2>');
    }

    public function testManageActionTemplateCsrfToken()
    {
        /** @var MockObject|Csrf */
        $csrf = $this->createStub(Csrf::class);
        $csrf->method('getValue')->willReturn('token');

        $this->_showDuplicates->method('get')->with('_csrf')->willReturn($csrf);
        $this->_duplicates->method('find')->willReturn([]);

        $this->dispatch('/console/duplicates/manage/');
        $this->assertXpathQuery('//input[@name="_csrf"][@value="token"]');
    }

    public function testManageActionTemplateTable()
    {
        /** @var MockObject|Client */
        $client1 = $this->createMock(Client::class);
        $client1->id = 1;
        $client1->name = 'name1';
        $client1->serial = 'serial1';
        $client1->assetTag = null;
        $client1->lastContactDate = new DateTime('2022-12-22T13:22:00');
        $client1->method('offsetGet')->with('NetworkInterface.MacAddress')->willReturn('mac1');

        /** @var MockObject|Client */
        $client2 = $this->createMock(Client::class);
        $client2->id = 2;
        $client2->name = 'name2';
        $client2->serial = null;
        $client2->assetTag = 'at2';
        $client2->lastContactDate = new DateTime('2023-01-28T17:27:00');
        $client2->method('offsetGet')->with('NetworkInterface.MacAddress')->willReturn('mac2');

        $this->_duplicates->method('find')->willReturn([$client1, $client2]);

        $this->dispatch('/console/duplicates/manage/');

        $this->assertXpathQueryCount('//tr[td]', 2);

        $this->assertXpathQuery('//tr[2]/td[1]/input[@value="1"]');
        $this->assertXpathQuery('//tr[2]/td[2]/a[contains(@href, "id=1")][text()="name1"]');
        $this->assertXpathQuery(
            '//tr[2]/td[3]/a[contains(@href, "criteria=MacAddress")][contains(@href, "value=mac1")][text()="mac1"]'
        );
        $this->assertXpathQuery(
            '//tr[2]/td[4]/a[contains(@href, "criteria=Serial")][contains(@href, "value=serial1")][text()="serial1"]'
        );
        $this->assertXpathQuery('//tr[2]/td[5][not(a)]');
        $this->assertXpathQuery('//tr[2]/td[6][normalize-space(text())="22.12.22, 13:22"]');

        $this->assertXpathQuery('//tr[3]/td[1]/input[@value="2"]');
        $this->assertXpathQuery('//tr[3]/td[2]/a[contains(@href, "id=2")][text()="name2"]');
        $this->assertXpathQuery(
            '//tr[3]/td[3]/a[contains(@href, "criteria=MacAddress")][contains(@href, "value=mac2")][text()="mac2"]'
        );
        $this->assertXpathQuery('//tr[3]/td[4][not(a)]');
        $this->assertXpathQuery(
            '//tr[3]/td[5]/a[contains(@href, "criteria=AssetTag")][contains(@href, "value=at2")][text()="at2"]'
        );
        $this->assertXpathQuery('//tr[3]/td[6][normalize-space(text())="28.01.23, 17:27"]');
    }

    public function testManageActionTemplateOptionsUnset()
    {
        $this->config->method('__get')->willReturn(false);
        $this->_duplicates->method('find')->willReturn([]);

        $this->dispatch('/console/duplicates/manage/');

        $this->assertXpathQuery('//input[@value="mergeCustomFields"][not(@checked)]');
        $this->assertXpathQuery('//input[@value="mergeConfig"][not(@checked)]');
        $this->assertXpathQuery('//input[@value="mergeGroups"][not(@checked)]');
        $this->assertXpathQuery('//input[@value="mergePackages"][not(@checked)]');
        $this->assertXpathQuery('//input[@value="mergeProductKey"][not(@checked)]');
    }

    public function testManageActionTemplateOptionsSet()
    {
        $this->config->method('__get')->willReturn(true);
        $this->_duplicates->method('find')->willReturn([]);

        $this->dispatch('/console/duplicates/manage/');

        $this->assertXpathQuery('//input[@value="mergeCustomFields"][@checked]');
        $this->assertXpathQuery('//input[@value="mergeConfig"][@checked]');
        $this->assertXpathQuery('//input[@value="mergeGroups"][@checked]');
        $this->assertXpathQuery('//input[@value="mergePackages"][@checked]');
        $this->assertXpathQuery('//input[@value="mergeProductKey"][@checked]');
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
