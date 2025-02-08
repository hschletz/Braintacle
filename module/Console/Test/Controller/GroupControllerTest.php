<?php

/**
 * Tests for GroupController
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

use Console\Form\ClientConfig;
use Console\Test\AbstractControllerTestCase;
use Console\View\Helper\Form\ClientConfig as FormClientConfig;
use Console\View\Helper\GroupHeader;
use Laminas\I18n\View\Helper\DateFormat;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for GroupController
 */
class GroupControllerTest extends AbstractControllerTestCase
{
    /**
     * Group manager mock
     * @var MockObject|GroupManager
     */
    protected $_groupManager;

    /**
     * Client configuration form mock
     * @var MockObject|ClientConfig
     */
    protected $_clientConfigForm;

    /**
     * Set up mock objects
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_groupManager = $this->createMock('Model\Group\GroupManager');
        $this->_clientConfigForm = $this->createMock('Console\Form\ClientConfig');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Group\GroupManager', $this->_groupManager);
        $formManager = $serviceManager->get('FormElementManager');
        $formManager->setService('Console\Form\ClientConfig', $this->_clientConfigForm);
    }

    public function testInvalidGroup()
    {
        $this->_groupManager->expects($this->once())
            ->method('getGroup')
            ->with('test')
            ->will($this->throwException(new \RuntimeException()));
        $this->dispatch('/console/group/general/?name=test');
        $this->assertRedirectTo('/console/group/index/');
        $this->assertContains(
            'Die angeforderte Gruppe existiert nicht.',
            $this->getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testGeneralAction()
    {
        $url = '/console/group/general/?name=test';
        $creationDate = new \DateTime();

        /** @var MockObject|Group */
        $group = $this->createMock(Group::class);
        $group->method('offsetGet')->willReturnMap([
            ['Name', 'groupName'],
            ['Id', 'groupID'],
            ['Description', 'groupDescription'],
            ['CreationDate', $creationDate],
            ['DynamicMembersSql', 'groupSql'],
        ]);
        $group->method('__get')->with('name')->willReturn('test');

        $this->_groupManager->expects($this->once())
            ->method('getGroup')
            ->with('test')
            ->willReturn($group);

        $dateFormat = $this->createMock(DateFormat::class);
        $dateFormat->expects($this->once())
            ->method('__invoke')
            ->with($creationDate, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM)
            ->willReturn('date_create');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('dateFormat', $dateFormat);

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='/route/showGroupGeneral?name=test']",
            'Allgemein'
        );
        $this->assertXpathQuery('//td[text()="Name"]/following::td[text()="groupName"]');
        $this->assertXpathQuery('//td[text()="ID"]/following::td[text()="groupID"]');
        $this->assertXpathQuery('//td[text()="Beschreibung"]/following::td[text()="groupDescription"]');
        $this->assertXpathQuery(
            '//td[text()="Erstellungsdatum"]/following::td[text()="date_create"]'
        );
        $this->assertXpathQuery("//td[text()='SQL-Abfrage']/following::td/code[text()='\ngroupSql\n']");
    }

    public function testConfigurationActionGet()
    {
        $config = array('name' => 'value');
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->once())->method('getAllConfig')->willReturn($config);
        $this->_groupManager->expects($this->once())
            ->method('getGroup')
            ->with('test')
            ->willReturn($group);
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
            ->method('setClientObject')
            ->with($group);
        $form->expects($this->once())
            ->method('setData')
            ->with($config);
        $form->expects($this->never())
            ->method('isValid');
        $form->expects($this->never())
            ->method('process');

        $formHelper = $this->createMock(FormClientConfig::class);
        $formHelper->method('__invoke')->with($form)->willReturn('<form></form>');

        $viewHelperManager = $this->getApplicationServiceLocator()->get('ViewHelperManager');
        $viewHelperManager->setService('consoleFormClientConfig', $formHelper);
        $viewHelperManager->setService(GroupHeader::class, $this->createStub(GroupHeader::class));

        $this->dispatch('/console/group/configuration/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostInvalid()
    {
        $group = $this->createMock('Model\Group\Group');
        $this->_groupManager->expects($this->once())
            ->method('getGroup')
            ->with('test')
            ->willReturn($group);
        $postData = array('key' => 'value');
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
            ->method('setClientObject')
            ->with($group);
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $form->expects($this->never())
            ->method('process');

        $formHelper = $this->createMock(FormClientConfig::class);
        $formHelper->method('__invoke')->with($form)->willReturn('<form></form>');

        $viewHelperManager = $this->getApplicationServiceLocator()->get('ViewHelperManager');
        $viewHelperManager->setService('consoleFormClientConfig', $formHelper);
        $viewHelperManager->setService(GroupHeader::class, $this->createStub(GroupHeader::class));

        $this->dispatch('/console/group/configuration/?name=test', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostValid()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $this->_groupManager->expects($this->once())
            ->method('getGroup')
            ->with('test')
            ->willReturn($group);
        $postData = array('key' => 'value');
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
            ->method('setClientObject')
            ->with($group);
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('process');

        $this->dispatch('/console/group/configuration/?name=test', 'POST', $postData);
        $this->assertRedirectTo('/console/group/configuration/?name=test');
    }
}
