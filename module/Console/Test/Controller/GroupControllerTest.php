<?php

/**
 * Tests for GroupController
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

use Console\Form\AddToGroup as FormAddToGroup;
use Console\Form\ClientConfig;
use Console\Form\Package\AssignPackagesForm;
use Console\View\Helper\Form\AddToGroup;
use Console\View\Helper\Form\ClientConfig as FormClientConfig;
use Console\View\Helper\GroupHeader;
use Laminas\I18n\View\Helper\DateFormat;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Model\Client\ClientManager;
use Model\Group\Group;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for GroupController
 */
class GroupControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Group manager mock
     * @var MockObject|GroupManager
     */
    protected $_groupManager;

    /**
     * Client manager mock
     * @var MockObject|ClientManager
     */
    protected $_clientManager;

    /**
     * Add to group form mock
     * @var MockObject|FormAddToGroup
     */
    protected $_addToGroupForm;

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
        $this->_clientManager = $this->createMock('Model\Client\ClientManager');
        $this->_addToGroupForm = $this->createMock('Console\Form\AddToGroup');
        $this->_clientConfigForm = $this->createMock('Console\Form\ClientConfig');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Group\GroupManager', $this->_groupManager);
        $serviceManager->setService('Model\Client\ClientManager', $this->_clientManager);
        $formManager = $serviceManager->get('FormElementManager');
        $formManager->setService('Console\Form\AddToGroup', $this->_addToGroupForm);
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

    public function testIndexActionNoData()
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(new \EmptyIterator());
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name', 'asc')
                            ->willReturn($resultSet);
        $this->dispatch('/console/group/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nKeine Gruppen definiert.\n']");
    }

    public function testIndexActionWithData()
    {
        $creationDate = new \DateTime('2014-04-06 11:55:33');
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(
            array(
                array(
                    'Name' => 'test',
                    'CreationDate' => $creationDate,
                    'Description' => 'description',
                )
            )
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name', 'asc')
                            ->willReturn($resultSet);

        $dateFormat = $this->createMock(DateFormat::class);
        $dateFormat->expects($this->once())
                   ->method('__invoke')
                   ->with($creationDate, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   ->willReturn('date_create');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('dateFormat', $dateFormat);

        $this->dispatch('/console/group/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/group/general/?name=test"]',
            'test'
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\ndate_create\n"
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\ndescription\n"
        );
        $this->assertNotXpathQuery("//p[@class='textcenter'][text()='\nKeine Gruppen definiert.\n']");
    }

    public function testIndexActionMessages()
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(new \EmptyIterator());
        $this->_groupManager->expects($this->once())->method('getGroups')->willReturn($resultSet);

        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->method('__invoke')->with(null)->willReturnSelf();
        $flashMessenger->method('__call')
                       ->withConsecutive(
                           array('getMessagesFromNamespace', array('error')),
                           array('getMessagesFromNamespace', array('success'))
                       )->willReturnOnConsecutiveCalls(array('error'), array('success'));
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('flashMessenger', $flashMessenger);

        $this->disableTranslator();
        $this->dispatch('/console/group/index/');
        $this->assertXpathQuery('//ul[@class="error"]/li[text()="error"]');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="success"]');
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
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
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

    public function testMembersAction()
    {
        $url = '/console/group/members/?name=test';
        $cacheCreationDate = new \DateTime('2014-04-08 20:12:21');
        $cacheExpirationDate = new \DateTime('2014-04-09 18:53:21');
        $inventoryDate = new \DateTime('2014-04-09 18:56:12');

        /** @var MockObject|Group */
        $group = $this->createMock(Group::class);
        $group->method('offsetGet')->willReturnMap([
            ['CacheCreationDate', $cacheCreationDate],
            ['CacheExpirationDate', $cacheExpirationDate],
        ]);
        $group->method('__get')->with('name')->willReturn('test');

        $clients = array(
            array(
                'Id' => '1',
                'Name' => 'clientName',
                'UserName' => 'userName',
                'InventoryDate' => $inventoryDate,
                'Membership' => \Model\Client\Client::MEMBERSHIP_ALWAYS,
            ),
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'Membership'),
                                 'InventoryDate',
                                 'desc',
                                 'MemberOf',
                                 $group
                             )
                             ->willReturn($clients);

        $dateFormat = $this->createMock(DateFormat::class);
        $dateFormat->expects($this->exactly(3))
                   ->method('__invoke')
                   ->withConsecutive(
                       array($cacheCreationDate, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM),
                       array($cacheExpirationDate, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM),
                       array($inventoryDate, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   )
                   ->will($this->onConsecutiveCalls('date_create', 'date_expire', 'date_client'));
        $viewHelperManager = $this->getApplicationServiceLocator()->get('ViewHelperManager');
        $viewHelperManager->setService('dateFormat', $dateFormat);

        $this->dispatch($url);

        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Mitglieder'
        );
        $this->assertXpathQuery(
            '//td[text()="Letztes Update:"]/following::td[text()="date_create"]'
        );
        $this->assertXpathQuery(
            '//td[text()="Nächstes Update:"]/following::td[text()="date_expire"]'
        );
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nAnzahl Clients: 1\n']");
        $this->assertXpathQuery("//td[text()='\nmanuell\n']");
        $this->assertXpathQuery("//td/a[@href='/console/client/groups/?id=1'][text()='clientName']");
    }

    public function testExcludedAction()
    {
        $url = '/console/group/excluded/?name=test';

        $group = new Group();
        $group->name = 'test';

        $clients = array(
            array(
                'Id' => '1',
                'Name' => 'clientName',
                'UserName' => 'userName',
                'InventoryDate' => new \DateTime('2014-04-09 18:56:12'),
            ),
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate'),
                                 'InventoryDate',
                                 'desc'
                             )
                             ->willReturn($clients);
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Ausgeschlossen'
        );
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nAnzahl Clients: 1\n']");
        $this->assertXpathQuery("//td/a[@href='/console/client/groups/?id=1'][text()='clientName']");
    }

    public function testPackagesActionOnlyAssigned()
    {
        $url = '/console/group/packages/?name=test';
        $packages = ['package1', 'package2'];

        /** @var MockObject|Group */
        $group = $this->createMock(Group::class);
        $group->expects($this->once())->method('getPackages')->with('asc')->willReturn($packages);
        $group->expects($this->once())->method('getAssignablePackages')->willReturn([]);
        $group->method('__get')->with('name')->willReturn('test');

        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Pakete'
        );
        $this->assertXpathQuery('//td[text()="package1"]');
        $this->assertXpathQuery('//td[text()="package2"]');
        $this->assertXpathQuery(
            '//td/a[@href="/console/group/removepackage/?name=test&package=package2"][normalize-space(text())="entfernen"]'
        );
    }

    public function testPackagesActionOnlyAvailable()
    {
        $url = '/console/group/packages/?name=test';
        $assignablePackages = ['package'];

        /** @var MockObject|Group */
        $group = $this->createMock(Group::class);
        $group->expects($this->once())->method('getPackages')->with('asc')->willReturn([]);
        $group->expects($this->once())->method('getAssignablePackages')->willReturn($assignablePackages);

        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery('//form');
    }

    public function testRemovepackageActionGet()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->never())->method('removePackage');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->dispatch('/console/group/removepackage/?package=package&name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            '//p[text()="Paket \'package\' wird nicht mehr dieser Gruppe zugewiesen sein. Fortfahren?"]'
        );
    }

    public function testRemovepackageActionPostNo()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->never())->method('removePackage');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->dispatch(
            '/console/group/removepackage/?package=package&name=test',
            'POST',
            array('no' => 'No')
        );
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testRemovepackageActionPostYes()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->expects($this->once())->method('removePackage')->with('package');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->dispatch(
            '/console/group/removepackage/?package=package&name=test',
            'POST',
            array('yes' => 'Yes')
        );
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testassignpackageActionGet()
    {
        /** @var MockObject|AssignPackagesForm */
        $form = $this->createMock(AssignPackagesForm::class);
        $form->expects($this->never())->method('process');

        $this->getApplicationServiceLocator()->setService(AssignPackagesForm::class, $form);

        $this->dispatch('/console/group/assignpackage/?name=test', 'GET');
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testAssignpackageActionPost()
    {
        $postData = ['packages' => ['package1', 'package2']];

        $group = new Group();
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        /** @var MockObject|AssignPackagesForm */
        $form = $this->createMock(AssignPackagesForm::class);
        $form->expects($this->once())->method('process')->with($postData, $group);

        $this->getApplicationServiceLocator()->setService(AssignPackagesForm::class, $form);

        $this->dispatch('/console/group/assignpackage/?name=test', 'POST', $postData);
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testAddActionGet()
    {
        $this->_addToGroupForm->expects($this->never())
                              ->method('setData');
        $this->_addToGroupForm->expects($this->never())
                              ->method('isValid');
        $this->_addToGroupForm->expects($this->never())
                              ->method('process');

        $formHelper = $this->createMock(AddToGroup::class);
        $formHelper->method('__invoke')->with($this->_addToGroupForm)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService(
            'consoleFormAddToGroup',
            $formHelper
        );

        $this->dispatch(
            '/console/group/add?filter=filter&search=search&invert=invert&operator=operator'
        );
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testAddActionPostInvalid()
    {
        $postData = ['key' => 'value'];
        $this->_addToGroupForm->expects($this->once())
                              ->method('setData')
                              ->with($postData);
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_addToGroupForm->expects($this->never())
                              ->method('process');

        $formHelper = $this->createMock(AddToGroup::class);
        $formHelper->method('__invoke')->with($this->_addToGroupForm)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService(
            'consoleFormAddToGroup',
            $formHelper
        );

        $this->dispatch(
            '/console/group/add?filter=filter&search=search&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testAddActionPostValid()
    {
        $postData = array('key' => 'value');
        $this->_addToGroupForm->expects($this->once())
                              ->method('setData')
                              ->with($postData);
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(true));
        $this->_addToGroupForm->expects($this->once())
                              ->method('process')
                              ->with('filter', 'search', 'operator', 'invert')
                              ->will($this->returnValue(array('Name' => 'test')));

        $this->dispatch(
            '/console/group/add?filter=filter&search=search&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertRedirectTo('/console/group/members/?name=test');
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

    public function testDeleteActionGet()
    {
        $group = array('Name' => 'test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->dispatch('/console/group/delete/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[contains(text(), "\'test\'")]');
    }

    public function testDeleteActionPostNo()
    {
        $group = array('Name' => 'test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_groupManager->expects($this->never())->method('deleteGroup');
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/group/general/?name=test');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_groupManager->expects($this->once())->method('deleteGroup')->with($group);
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            ["Gruppe 'test' wurde erfolgreich gelöscht."],
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            array(),
            $this->getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testDeleteActionPostYesError()
    {
        $group = $this->createMock('Model\Group\Group');
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_groupManager->expects($this->once())
                            ->method('deleteGroup')
                            ->with($group)
                            ->will($this->throwException(new \Model\Group\RuntimeException()));
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            array(),
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            ["Gruppe 'test' konnte nicht gelöscht werden. Bitte erneut versuchen."],
            $this->getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }
}
