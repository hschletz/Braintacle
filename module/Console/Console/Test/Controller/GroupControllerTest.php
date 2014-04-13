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
     * @var \Form_AffectPackages
     */
    protected $_packageAssignmentForm;

    /**
     * Add to group form mock
     * @var \Form_AddToGroup
     */
    protected $_addToGroupForm;

    /**
     * Client configuration form mock
     * @var \Form_Configuration
     */
    protected $_clientConfigForm;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_group = $this->getMockBuilder('Model_Group')->disableOriginalConstructor()->getMock();
        $this->_computer = $this->getMockBuilder('Model_Computer')->disableOriginalConstructor()->getMock();
        $this->_packageAssignmentForm = $this->getMock('Form_AffectPackages');
        $this->_addToGroupForm = $this->getMockBuilder('Form_AddToGroup')->disableOriginalConstructor()->getMock();
        $this->_clientConfigForm = $this->getMock('Form_Configuration');
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
                     ->method('fetchById')
                     ->with(42)
                     ->will($this->throwException(new \RuntimeException));
        $this->dispatch('/console/group/general/?id=42');
        $this->assertRedirectTo('/console/group/index/');
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
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nNo groups defined.\n']");
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
                                     'Id' => 1,
                                     'Name' => 'name',
                                     'CreationDate' => new \Zend_Date('2014-04-06 11:55:33'),
                                     'Description' => 'description',
                                 )
                             )
                         )
                     );
        $this->dispatch('/console/group/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/group/general/?id=1"]',
            'name'
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\n06.04.14 11:55\n"
        );
        $this->assertXpathQueryContentContains(
            '//td',
            "\ndescription\n"
        );
        $this->assertNotXpathQuery("//p[@class='textcenter'][text()='\nNo groups defined.\n']");
    }

    public function testIndexActionMessages()
    {
        // Test empty group list
        $this->_group->expects($this->once())
                     ->method('fetch')
                     ->will($this->returnValue(array()));
        $this->_sessionSetup = array(
            'FlashMessenger' => array(
                'error' => new \Zend\Stdlib\SplQueue,
                'success' => new \Zend\Stdlib\SplQueue,
            ),
        );
        $this->_sessionSetup['FlashMessenger']['error']->enqueue('error');
        $this->_sessionSetup['FlashMessenger']['success']->enqueue('success');
        $this->dispatch('/console/group/index/');
        $this->assertXpathQuery('//ul[@class="error"]/li[text()="error"]');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="success"]');
    }

    public function testGeneralAction()
    {
        $url = '/console/group/general/?id=42';
        $group = array(
            'Name' => 'groupName',
            'Id' => 'groupID',
            'Description' => 'groupDescription',
            'CreationDate' => new \Zend_Date('2014-04-08 20:12:21'),
            'DynamicMembersSql' => 'groupSql',
        );
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnValue($group));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'General'
        );
        $this->assertXpathQuery('//td[text()="Name"]/following::td[text()="groupName"]');
        $this->assertXpathQuery('//td[text()="ID"]/following::td[text()="groupID"]');
        $this->assertXpathQuery('//td[text()="Description"]/following::td[text()="groupDescription"]');
        $this->assertXpathQuery(
            '//td[text()="Creation date"]/following::td[text()="Dienstag, 8. April 2014 20:12:21"]'
        );
        $this->assertXpathQuery("//td[text()='SQL query']/following::td/code[text()='\ngroupSql\n']");
    }

    public function testMembersAction()
    {
        $url = '/console/group/members/?id=42';
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
                     ->method('fetchById')
                     ->with('42')
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
            'Members'
        );
        $this->assertXpathQuery('//td[text()="Last update:"]/following::td[text()="Dienstag, 8. April 2014 20:12:21"]');
        $this->assertXpathQuery('//td[text()="Next update:"]/following::td[text()="Mittwoch, 9. April 2014 18:53:21"]');
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nNumber of computers: 1\n']");
        $this->assertXpathQuery("//td[text()='\nmanual\n']");
        $this->assertXpathQuery("//td/a[@href='/console/computer/groups/?id=1'][text()='computerName']");
    }

    public function testExcludedAction()
    {
        $url = '/console/group/excluded/?id=42';
        $group = array('Name' => 'groupName');
        $computers = array(
            array(
                'Id' => '1',
                'Name' => 'computerName',
                'UserName' => 'userName',
                'InventoryDate' => new \Zend_Date('2014-04-09 18:56:12'),
            ),
        );
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
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
            'Excluded'
        );
        $this->assertXpathQuery("//p[@class='textcenter'][text()='\nNumber of computers: 1\n']");
        $this->assertXpathQuery("//td/a[@href='/console/computer/groups/?id=1'][text()='computerName']");
    }

    public function testPackagesActionOnlyAssigned()
    {
        $url = '/console/group/packages/?id=42';
        $packages = array('package1', 'package2');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
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
                                array('Id', 42),
                                array('Name', 'groupName')
                            )
                         )
                     );
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('addPackages')
                                     ->with($this->_group)
                                     ->will($this->returnValue(0));
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('__toString');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Packages'
        );
        $this->assertXpathQuery("//td[text()='\npackage2\n']");
        $this->assertXpathQuery(
            "//td/a[@href='/console/group/removepackage/?name=package2&id=42'][text()='remove']"
        );
    }

    public function testPackagesActionOnlyAvailable()
    {
        $url = '/console/group/packages/?id=42';
        $packages = array();
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
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
                                array('Id', 42),
                                array('Name', 'groupName')
                            )
                         )
                     );
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('addPackages')
                                     ->with($this->_group)
                                     ->will($this->returnValue(2));
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('setAction')
                                     ->with('/console/group/installpackage/?id=42');
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('__toString')
                                     ->will($this->returnValue(''));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('table');
    }

    public function testRemovepackageActionGet()
    {
        $group = array('Id' => '42');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnValue($group));
        $this->_group->expects($this->never())
                     ->method('unaffectPackage');
        $this->dispatch('/console/group/removepackage/?name=package&id=42');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[contains(text(), \'"package"\')]');
    }

    public function testRemovepackageActionPostNo()
    {
        $group = array('Id' => '42');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnValue($group));
        $this->_group->expects($this->never())
                     ->method('unaffectPackage');
        $this->dispatch(
            '/console/group/removepackage/?name=package&id=42',
            'POST',
            array('no' => 'No')
        );
        $this->assertRedirectTo('/console/group/packages/?id=42');
    }

    public function testRemovepackageActionPostYes()
    {
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('unaffectPackage')
                     ->with('package');
        $this->dispatch(
            '/console/group/removepackage/?name=package&id=42',
            'POST',
            array('yes' => 'Yes')
        );
        $this->assertRedirectTo('/console/group/packages/?id=42');
    }

    public function testInstallpackageActionGet()
    {
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->never())
                     ->method('installPackage');
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('isValid');
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('getValues');

        $this->dispatch('/console/group/installpackage/?id=42');
        $this->assertRedirectTo('/console/group/packages/?id=42');
    }

    public function testInstallpackageActionPostInvalid()
    {
        $filter = new \Braintacle_Filter_FormElementNameEncode;
        $postData = array(
            $filter->filter('package1') => '0',
            $filter->filter('package2') => '1',
        );
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->never())
                     ->method('installPackage');
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('isValid')
                                     ->with($postData)
                                     ->will($this->returnValue(false));
        $this->_packageAssignmentForm->expects($this->never())
                                     ->method('getValues');

        $this->dispatch('/console/group/installpackage/?id=42', 'POST', $postData);
        $this->assertRedirectTo('/console/group/packages/?id=42');
    }

    public function testInstallpackageActionPostValid()
    {
        $filter = new \Braintacle_Filter_FormElementNameEncode;
        $postData = array(
            $filter->filter('package1') => '0',
            $filter->filter('package2') => '1',
        );
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('installPackage')
                     ->with('package2');
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('isValid')
                                     ->with($postData)
                                     ->will($this->returnValue(true));
        $this->_packageAssignmentForm->expects($this->once())
                                     ->method('getValues')
                                     ->will($this->returnValue(array('package2' => '1')));

        $this->dispatch('/console/group/installpackage/?id=42', 'POST', $postData);
        $this->assertRedirectTo('/console/group/packages/?id=42');
    }

    public function testAddActionGet()
    {
        $this->_computer->expects($this->never())
                        ->method('fetch');
        $this->_addToGroupForm->expects($this->never())
                              ->method('isValid');
        $this->_addToGroupForm->expects($this->never())
                              ->method('getGroup');
        $this->_addToGroupForm->expects($this->never())
                              ->method('getValue');
        $this->_addToGroupForm->expects($this->once())
                              ->method('__toString')
                              ->will($this->returnValue(''));
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&exact=exact&invert=invert&operator=operator'
        );
        $this->assertResponseStatusCode(200);
    }

    public function testAddActionPostInvalid()
    {
        $postData = array();
        $this->_computer->expects($this->never())
                        ->method('fetch');
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->with($postData)
                              ->will($this->returnValue(false));
        $this->_addToGroupForm->expects($this->never())
                              ->method('getGroup');
        $this->_addToGroupForm->expects($this->never())
                              ->method('getValue');
        $this->_addToGroupForm->expects($this->once())
                              ->method('__toString')
                              ->will($this->returnValue(''));
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&exact=exact&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertResponseStatusCode(200);
    }

    public function testAddActionPostValidStoreFilter()
    {
        $postData = array('What' => \Form_AddToGroup::STORE_FILTER);
        $members = $this->getMockBuilder('Zend_Db_Select')->disableOriginalConstructor()->getMock();
        $group = $this->getMockBuilder('Model_Group')->disableOriginalConstructor()->getMock();
        $group->expects($this->once())
              ->method('__call')
              ->with('setDynamicMembersSql', array($members));
        $group->expects($this->never())
              ->method('addComputers');
        $group->expects($this->never())
              ->method('excludeComputers');
        $group->expects($this->once())
              ->method('offsetGet')
              ->with('Id')
              ->will($this->returnValue(42));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Id'),
                            null,
                            null,
                            'filter',
                            'search',
                            'exact',
                            'invert',
                            'operator',
                            false,
                            true,
                            false
                        )
                        ->will($this->returnValue($members));
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->with($postData)
                              ->will($this->returnValue(true));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getGroup')
                              ->will($this->returnValue($group));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getValue')
                              ->with('What')
                              ->will($this->returnValue($postData['What']));
        $this->_addToGroupForm->expects($this->never())
                              ->method('__toString');
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&exact=exact&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertRedirectTo('/console/group/members/?id=42');
    }

    public function testAddActionPostValidStoreResult()
    {
        $postData = array('What' => \Form_AddToGroup::STORE_RESULT);
        $members = array(1, 2);
        $group = $this->getMockBuilder('Model_Group')->disableOriginalConstructor()->getMock();
        $group->expects($this->never())
              ->method('__call');
        $group->expects($this->once())
              ->method('addComputers')
              ->with($members);
        $group->expects($this->never())
              ->method('excludeComputers');
        $group->expects($this->once())
              ->method('offsetGet')
              ->with('Id')
              ->will($this->returnValue(42));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Id'),
                            null,
                            null,
                            'filter',
                            'search',
                            'exact',
                            'invert',
                            'operator',
                            false,
                            true,
                            true
                        )
                        ->will($this->returnValue($members));
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->with($postData)
                              ->will($this->returnValue(true));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getGroup')
                              ->will($this->returnValue($group));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getValue')
                              ->with('What')
                              ->will($this->returnValue($postData['What']));
        $this->_addToGroupForm->expects($this->never())
                              ->method('__toString');
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&exact=exact&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertRedirectTo('/console/group/members/?id=42');
    }

    public function testAddActionPostValidStoreExcluded()
    {
        $postData = array('What' => \Form_AddToGroup::STORE_EXCLUDED);
        $members = array(1, 2);
        $group = $this->getMockBuilder('Model_Group')->disableOriginalConstructor()->getMock();
        $group->expects($this->never())
              ->method('__call');
        $group->expects($this->never())
              ->method('addComputers');
        $group->expects($this->once())
              ->method('excludeComputers')
              ->with($members);
        $group->expects($this->once())
              ->method('offsetGet')
              ->with('Id')
              ->will($this->returnValue(42));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Id'),
                            null,
                            null,
                            'filter',
                            'search',
                            'exact',
                            'invert',
                            'operator',
                            false,
                            true,
                            true
                        )
                        ->will($this->returnValue($members));
        $this->_addToGroupForm->expects($this->once())
                              ->method('isValid')
                              ->with($postData)
                              ->will($this->returnValue(true));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getGroup')
                              ->will($this->returnValue($group));
        $this->_addToGroupForm->expects($this->once())
                              ->method('getValue')
                              ->with('What')
                              ->will($this->returnValue($postData['What']));
        $this->_addToGroupForm->expects($this->never())
                              ->method('__toString');
        $this->dispatch(
            '/console/group/add?filter=filter&search=search&exact=exact&invert=invert&operator=operator',
            'POST',
            $postData
        );
        $this->assertRedirectTo('/console/group/members/?id=42');
    }

    public function testConfigurationActionGet()
    {
        $url = '/console/group/configuration/?id=42';
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_clientConfigForm->expects($this->once())
                                ->method('setObject')
                                ->with($this->_group);
        $this->_clientConfigForm->expects($this->never())
                                ->method('isValid');
        $this->_clientConfigForm->expects($this->never())
                                ->method('process');
        $this->_clientConfigForm->expects($this->once())
                                ->method('__toString')
                                ->will($this->returnValue(''));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            "//ul[@class='navigation navigation_details']/li[@class='active']/a[@href='$url']",
            'Configuration'
        );
    }

    public function testConfigurationPostInvalid()
    {
        $postData = array('key' => 'value');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_clientConfigForm->expects($this->once())
                                ->method('setObject')
                                ->with($this->_group);
        $this->_clientConfigForm->expects($this->once())
                                ->method('isValid')
                                ->with($postData)
                                ->will($this->returnValue(false));
        $this->_clientConfigForm->expects($this->never())
                                ->method('process');
        $this->_clientConfigForm->expects($this->once())
                                ->method('__toString')
                                ->will($this->returnValue(''));
        $this->dispatch('/console/group/configuration/?id=42', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testConfigurationPostValid()
    {
        $postData = array('key' => 'value');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Id')
                     ->will($this->returnValue(42));
        $this->_clientConfigForm->expects($this->once())
                                ->method('setObject')
                                ->with($this->_group);
        $this->_clientConfigForm->expects($this->once())
                                ->method('isValid')
                                ->with($postData)
                                ->will($this->returnValue(true));
        $this->_clientConfigForm->expects($this->once())
                                ->method('process');
        $this->_clientConfigForm->expects($this->never())
                                ->method('__toString');
        $this->dispatch('/console/group/configuration/?id=42', 'POST', $postData);
        $this->assertRedirectTo('/console/group/configuration/?id=42');
    }

    public function testDeleteActionGet()
    {
        $group = array('Name' => 'groupName');
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnValue($group));
        $this->dispatch('/console/group/delete/?id=42');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//p[contains(text(), "\'groupName\'")]');
    }

    public function testDeleteActionPostNo()
    {
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->exactly(2))
                     ->method('offsetGet')
                     ->will(
                         $this->returnValueMap(
                             array(
                                 array('Id', 42),
                                 array('Name', 'groupName'),
                             )
                         )
                     );
        $this->_group->expects($this->never())
                     ->method('delete');
        $this->dispatch('/console/group/delete/?id=42', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/group/general/?id=42');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $this->_group->expects($this->once())
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Name')
                     ->will($this->returnValue('groupName'));
        $this->_group->expects($this->once())
                     ->method('delete')
                     ->will($this->returnValue(true));
        $this->dispatch('/console/group/delete/?id=42', 'POST', array('yes' => 'Yes'));
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
                     ->method('fetchById')
                     ->with('42')
                     ->will($this->returnSelf());
        $this->_group->expects($this->once())
                     ->method('offsetGet')
                     ->with('Name')
                     ->will($this->returnValue('groupName'));
        $this->_group->expects($this->once())
                     ->method('delete')
                     ->will($this->returnValue(false));
        $this->dispatch('/console/group/delete/?id=42', 'POST', array('yes' => 'Yes'));
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
