<?php
/**
 * Tests for GroupController
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
 * Tests for GroupController
 */
class GroupControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Group manager mock
     * @var \Model\Group\GroupManager
     */
    protected $_groupManager;

    /**
     * Computer mock
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Package assignment form mock
     * @var \Console\Form\Package\Assign
     */
    protected $_packageAssignmentForm;

    /**
     * Add to group form mock
     * @var \Console\Form\AddToGroup
     */
    protected $_addToGroupForm;

    /**
     * Client configuration form mock
     * @var \Console\Form\ClientConfig
     */
    protected $_clientConfigForm;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_groupManager = $this->getMockBuilder('Model\Group\GroupManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->_computer = $this->getMockBuilder('Model_Computer')->disableOriginalConstructor()->getMock();
        $this->_packageAssignmentForm = $this->getMock('Console\Form\Package\Assign');
        $this->_addToGroupForm = $this->getMock('Console\Form\AddToGroup');
        $this->_clientConfigForm = $this->getMock('Console\Form\ClientConfig');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\GroupController(
            $this->_groupManager,
            $this->_computer,
            $this->_packageAssignmentForm,
            $this->_addToGroupForm,
            $this->_clientConfigForm
        );
    }

    public function testService()
    {
        $this->_overrideService('Model\Computer\Computer', $this->_computer);
        $this->_overrideService('Console\Form\AddToGroup', $this->_addToGroupForm);
        parent::testService();
    }

    public function testInvalidGroup()
    {
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->will($this->throwException(new \RuntimeException));
        $this->dispatch('/console/group/general/?name=test');
        $this->assertRedirectTo('/console/group/index/');
        $this->assertContains(
            'The requested group does not exist.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testIndexActionNoData()
    {
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize(array());
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
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize(
            array(
                array(
                    'Name' => 'test',
                    'CreationDate' => new \Zend_Date('2014-04-06 11:55:33'),
                    'Description' => 'description',
                )
            )
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name', 'asc')
                            ->willReturn($resultSet);

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->once())
                   ->method('__invoke')
                   ->with(1396778133, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   ->willReturn('date_create');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

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
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize(array());
        $this->_groupManager->expects($this->once())->method('getGroups')->willReturn($resultSet);
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addErrorMessage('error');
        $flashMessenger->addSuccessMessage('success');
        $this->_disableTranslator();
        $this->dispatch('/console/group/index/');
        $this->assertXpathQuery('//ul[@class="error"]/li[text()="error"]');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="success"]');
    }

    public function testGeneralAction()
    {
        $url = '/console/group/general/?name=test';
        $group = array(
            'Name' => 'groupName',
            'Id' => 'groupID',
            'Description' => 'groupDescription',
            'CreationDate' => new \Zend_Date('2014-04-08 20:12:21'),
            'DynamicMembersSql' => 'groupSql',
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->once())
                   ->method('__invoke')
                   ->with(1396980741, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM)
                   ->willReturn('date_create');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

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
        $group = array(
            'Name' => 'groupName',
            'CacheCreationDate' => new \Zend_Date('2014-04-08 20:12:21'),
            'CacheExpirationDate' => new \Zend_Date('2014-04-09 18:53:21'),
        );
        $computers = array(
            array(
                'Id' => '1',
                'Name' => 'computerName',
                'UserName' => 'userName',
                'InventoryDate' => new \Zend_Date('2014-04-09 18:56:12'),
                'Membership' => \Model_GroupMembership::TYPE_STATIC,
            ),
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'Membership'),
                            'InventoryDate',
                            'desc',
                            'MemberOf',
                            $group
                        )
                        ->will($this->returnValue($computers));

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->exactly(3))
                   ->method('__invoke')
                   ->withConsecutive(
                       array(1396980741, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM),
                       array(1397062401, \IntlDateFormatter::FULL, \IntlDateFormatter::MEDIUM),
                       array(1397062572, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   )
                   ->will($this->onConsecutiveCalls('date_create', 'date_expire', 'date_computer'));
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

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
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nAnzahl Computer: 1\n']");
        $this->assertXpathQuery("//td[text()='\nmanuell\n']");
        $this->assertXpathQuery("//td/a[@href='/console/client/groups/?id=1'][text()='computerName']");
    }

    public function testExcludedAction()
    {
        $url = '/console/group/excluded/?name=test';
        $group = array('Name' => 'test');
        $computers = array(
            array(
                'Id' => '1',
                'Name' => 'computerName',
                'UserName' => 'userName',
                'InventoryDate' => new \Zend_Date('2014-04-09 18:56:12'),
            ),
        );
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate'),
                            'InventoryDate',
                            'desc'
                        )
                        ->will($this->returnValue($computers));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Ausgeschlossen'
        );
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nAnzahl Computer: 1\n']");
        $this->assertXpathQuery("//td/a[@href='/console/client/groups/?id=1'][text()='computerName']");
    }

    public function testPackagesActionOnlyAssigned()
    {
        $url = '/console/group/packages/?name=test';
        $packages = array('package1', 'package2');

        $group = $this->getMock('Model_Group');
        $group->expects($this->once())->method('getPackages')->with('asc')->willReturn($packages);
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $group->expects($this->once())->method('getInstallablePackages')->willReturn(array());

        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('setPackages');
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('render');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Pakete'
        );
        $this->assertXpathQuery("//td[text()='\npackage2\n']");
        $this->assertXpathQuery(
            "//td/a[@href='/console/group/removepackage/?package=package2&name=test'][text()='entfernen']"
        );
    }

    public function testPackagesActionOnlyAvailable()
    {
        $url = '/console/group/packages/?name=test';
        $packages = array('package1', 'package2');

        $group = $this->getMock('Model_Group');
        $group->expects($this->once())->method('getPackages')->with('asc')->willReturn(array());
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $group->expects($this->once())->method('getInstallablePackages')->willReturn($packages);

        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);

        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('setPackages')
                                     ->with($packages);
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('render')
                                     ->will($this->returnValue('<form></form>'));
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('setAttribute')
                                     ->with('action', '/console/group/installpackage/?name=test');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery('//form');
    }

    public function testRemovepackageActionGet()
    {
        $group = $this->getMock('Model_Group');
        $group->expects($this->never())->method('unaffectPackage');
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
        $group = $this->getMock('Model_Group');
        $group->expects($this->never())->method('unaffectPackage');
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
        $group = $this->getMock('Model_Group');
        $group->expects($this->once())->method('unaffectPackage')->with('package');
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

    public function testInstallpackageActionGet()
    {
        $group = $this->getMock('Model_Group');
        $group->expects($this->never())->method('installPackage');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('isValid');
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('setData');
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('getData');

        $this->dispatch('/console/group/installpackage/?name=test');
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testInstallpackageActionPostInvalid()
    {
        $postData = array('Packages' => array('package1' => '0', 'package2' => '1'));
        $group = $this->getMock('Model_Group');
        $group->expects($this->never())->method('installPackage');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('isValid')
                                     ->will($this->returnValue(false));
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('setData')
                                     ->with($postData);
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('getData');
        $this->dispatch('/console/group/installpackage/?name=test', 'POST', $postData);
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testInstallpackageActionPostValid()
    {
        $postData = array('Packages' => array('package1' => '0', 'package2' => '1'));
        $group = $this->getMock('Model_Group');
        $group->expects($this->once())->method('installPackage')->with('package2');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('isValid')
                                     ->will($this->returnValue(true));
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('setData')
                                     ->with($postData);
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('getData')
                                     ->will($this->returnValue($postData));
        $this->dispatch('/console/group/installpackage/?name=test', 'POST', $postData);
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
        $this->_addToGroupForm->expects($this->once())
                              ->method('render')
                              ->will($this->returnValue('<form></form>'));
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&invert=invert&operator=operator'
        );
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testAddActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $this->_addToGroupForm->expects($this->once())
                              ->method('setData')
                              ->with($postData);
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_addToGroupForm->expects($this->never())
                              ->method('process');
        $this->_addToGroupForm->expects($this->once())
                              ->method('render')
                              ->will($this->returnValue('<form></form>'));
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
        $this->_addToGroupForm->expects($this->never())
                              ->method('render');
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertRedirectTo('/console/group/members/?name=test');
    }

    /**
     * @medium
     */
    public function testConfigurationActionGet()
    {
        $config = array('name' => 'value');
        $group = $this->getMock('Model_Group');
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
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/group/configuration/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    /**
     * @medium
     */
    public function testConfigurationActionPostInvalid()
    {
        $group = $this->getMock('Model_Group');
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
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/group/configuration/?name=test', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostValid()
    {
        $group = $this->getMock('Model_Group');
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
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $group = $this->getMock('Model_Group');
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_groupManager->expects($this->once())->method('deleteGroup')->with($group);
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            array(array('Group \'%s\' was successfully deleted.' => 'test')),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            array(),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testDeleteActionPostYesError()
    {
        $group = $this->getMock('Model_Group');
        $group->method('offsetGet')->with('Name')->willReturn('test');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('test')
                            ->willReturn($group);
        $this->_groupManager->expects($this->once())
                            ->method('deleteGroup')
                            ->with($group)
                            ->will($this->throwException(new \Model\Group\RuntimeException));
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            array(),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            array(array('Group \'%s\' could not be deleted. Try again later.' => 'test')),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }
}
