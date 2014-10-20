<?php
/**
 * Tests for GroupController
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
     * Group mock
     * @var \Model_Group
     */
    protected $_group;

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
        $this->_group = $this->getMockBuilder('Model_Group')->disableOriginalConstructor()->getMock();
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
            $this->_group,
            $this->_computer,
            $this->_packageAssignmentForm,
            $this->_addToGroupForm,
            $this->_clientConfigForm
        );
    }

    public function testService()
    {
        $this->_overrideService('Model\Group\Group', $this->_group);
        $this->_overrideService('Model\Computer\Computer', $this->_computer);
        $this->_overrideService('Console\Form\AddToGroup', $this->_addToGroupForm);
        parent::testService();
    }

    public function testInvalidGroup()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
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
        // Test empty group list
        $this->_group->expects($this->once())
                     ->method('fetch')
                     ->with(
                         array('Name', 'CreationDate', 'Description'),
                         null,
                         null,
                         'Name',
                         'asc'
                     )
                     ->will($this->returnValue(array()));
        $this->dispatch('/console/group/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nKeine Gruppen definiert.\n']");
    }

    public function testIndexActionWithData()
    {
        $this->_group->expects($this->once())
                     ->method('fetch')
                     ->with(
                         array('Name', 'CreationDate', 'Description'),
                         null,
                         null,
                         'Name',
                         'asc'
                     )
                     ->will(
                         $this->returnValue(
                             array(
                                 array(
                                     'Name' => 'test',
                                     'CreationDate' => new \Zend_Date('2014-04-06 11:55:33'),
                                     'Description' => 'description',
                                 )
                             )
                         )
                     );
        $this->dispatch('/console/group/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/group/general/?name=test"]',
            'test'
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\n06.04.14 11:55\n"
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\ndescription\n"
        );
        $this->assertNotXpathQuery("//p[@class='textcenter'][text()='\nKeine Gruppen definiert.\n']");
    }

    public function testIndexActionMessages()
    {
        // Test empty group list
        $this->_group->expects($this->once())
                     ->method('fetch')
                     ->will($this->returnValue(array()));
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addErrorMessage('error');
        $flashMessenger->addSuccessMessage('success');
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
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
            '//td[text()="Erstellungsdatum"]/following::td[text()="Dienstag, 8. April 2014 20:12:21"]'
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
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
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Mitglieder'
        );
        $this->assertXpathQuery('//td[text()="Letztes Update:"]/following::td[text()="Dienstag, 8. April 2014 20:12:21"]');
        $this->assertXpathQuery('//td[text()="Nächstes Update:"]/following::td[text()="Mittwoch, 9. April 2014 18:53:21"]');
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nAnzahl Computer: 1\n']");
        $this->assertXpathQuery("//td[text()='\nmanuell\n']");
        $this->assertXpathQuery("//td/a[@href='/console/computer/groups/?id=1'][text()='computerName']");
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
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
        $this->assertXpathQuery("//td/a[@href='/console/computer/groups/?id=1'][text()='computerName']");
    }

    public function testPackagesActionOnlyAssigned()
    {
        $url = '/console/group/packages/?name=test';
        $packages = array('package1', 'package2');
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('getPackages')
                     ->with('asc')
                     ->will($this->returnValue($packages));
        $this->_group->expects($this->atLeastOnce())
                     ->method('offsetGet')
                     ->will(
                         $this->returnValueMap(
                             array(
                                array('Name', 'test')
                            )
                         )
                     );
        $this->_group->expects($this->once())
                     ->method('getInstallablePackages')
                     ->will($this->returnValue(array()));
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('getPackages')
                     ->with('asc')
                     ->will($this->returnValue(array()));
        $this->_group->expects($this->atLeastOnce())
                     ->method('offsetGet')
                     ->will(
                         $this->returnValueMap(
                             array(
                                array('Name', 'test')
                            )
                         )
                     );
        $this->_group->expects($this->once())
                     ->method('getInstallablePackages')
                     ->will($this->returnValue($packages));
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
        $group = array('Name' => 'test');
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
        $this->_group->expects($this->never())
                     ->method('unaffectPackage');
        $this->dispatch('/console/group/removepackage/?package=package&name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            '//p[text()="Paket \'package\' wird nicht mehr dieser Gruppe zugewiesen sein. Fortfahren?"]'
        );
    }

    public function testRemovepackageActionPostNo()
    {
        $group = array('Name' => 'test');
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
        $this->_group->expects($this->never())
                     ->method('unaffectPackage');
        $this->dispatch(
            '/console/group/removepackage/?package=package&name=test',
            'POST',
            array('no' => 'No')
        );
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testRemovepackageActionPostYes()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('unaffectPackage')
                     ->with('package');
        $this->dispatch(
            '/console/group/removepackage/?package=package&name=test',
            'POST',
            array('yes' => 'Yes')
        );
        $this->assertRedirectTo('/console/group/packages/?name=test');
    }

    public function testInstallpackageActionGet()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->never())
                     ->method('installPackage');
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->never())
                     ->method('installPackage');
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('installPackage')
                     ->with('package2');
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

    public function testConfigurationActionGet()
    {
        $config = array('name' => 'value');
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('getAllConfig')
                     ->will($this->returnValue($config));
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_group);
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

    public function testConfigurationActionPostInvalid()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $postData = array('key' => 'value');
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_group);
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
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Name')
                     ->will($this->returnValue('test'));
        $postData = array('key' => 'value');
        $form = $this->_clientConfigForm;
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_group);
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
        $group = array('Name' => 'groupName');
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnValue($group));
        $this->dispatch('/console/group/delete/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[contains(text(), "\'groupName\'")]');
    }

    public function testDeleteActionPostNo()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->exactly(2))
                     ->method('offsetGet')
                     ->will(
                         $this->returnValueMap(
                             array(
                                 array('Name', 'test'),
                             )
                         )
                     );
        $this->_group->expects($this->never())
                     ->method('delete');
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/group/general/?name=test');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Name')
                     ->will($this->returnValue('groupName'));
        $this->_group->expects($this->once())
                     ->method('delete')
                     ->will($this->returnValue(true));
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            array(array('Group \'%s\' was successfully deleted.' => 'groupName')),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            array(),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testDeleteActionPostYesError()
    {
        $this->_group->expects($this->once())
                     ->method('fetchByName')
                     ->with('test')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Name')
                     ->will($this->returnValue('groupName'));
        $this->_group->expects($this->once())
                     ->method('delete')
                     ->will($this->returnValue(false));
        $this->dispatch('/console/group/delete/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/group/index/');
        $this->assertEquals(
            array(),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
        $this->assertEquals(
            array(array('Group \'%s\' could not be deleted.' => 'groupName')),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }
}
