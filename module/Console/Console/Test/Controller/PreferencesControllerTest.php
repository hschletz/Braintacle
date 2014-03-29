<?php
/**
 * Tests for PreferencesController
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
 * Tests for PreferencesController
 */
class PreferencesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Form manager mock
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $_formManager;

    /**
     * CustomFields mock
     * @var \Model_UserDefinedInfo
     */
    protected $_customFields;

    /**
     * DeviceType mock
     * @var \Model_NetworkDeviceType
     */
    protected $_deviceType;

    /**
     * RegistryValue mock
     * @var \Model_RegistryValue
     */
    protected $_registryValue;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_customFields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $this->_deviceType = $this->getMock('Model_NetworkDeviceType');
        $this->_registryValue = $this->getMock('Model_RegistryValue');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\PreferencesController(
            $this->_formManager,
            $this->_customFields,
            $this->_deviceType,
            $this->_registryValue
        );
    }

    /** {@inheritdoc} */
    public function testService()
    {
        $this->_overrideService('Model\Computer\CustomFields', $this->_customFields);
        parent::testService();
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $this->dispatch('/console/preferences/index/');
        $this->assertRedirectTo('/console/preferences/display/');
    }

    /**
     * Tests for displayAction()
     */
    public function testDisplayActionTest()
    {
        $this->_testUseForm('display', 'Display');
    }

    /**
     * Tests for inventoryAction()
     */
    public function testInventoryActionTest()
    {
        $this->_testUseForm('inventory', 'Inventory');
    }

    /**
     * Tests for agentAction()
     */
    public function testAgentActionTest()
    {
        $this->_testUseForm('agent', 'Agent');
    }

    /**
     * Tests for packagesAction()
     */
    public function testPackagesActionTest()
    {
        $this->_testUseForm('packages', 'Packages');
    }

    /**
     * Tests for downloadAction()
     */
    public function testDownloadActionTest()
    {
        $this->_testUseForm('download', 'Download');
    }

    /**
     * Tests for networkscanningAction()
     */
    public function testNetworkscanningActionTest()
    {
        $this->_testUseForm('networkscanning', 'NetworkScanning');
    }

    /**
     * Tests for groupsAction()
     */
    public function testGroupsActionTest()
    {
        $this->_testUseForm('groups', 'Groups');
    }

    /**
     * Tests for rawdataAction()
     */
    public function testRawdataActionTest()
    {
        $this->_testUseForm('rawdata', 'RawData');
    }

    /**
     * Tests for filtersAction()
     */
    public function testFiltersActionTest()
    {
        $this->_testUseForm('filters', 'Filters');
    }

    /**
     * Tests for systemAction()
     */
    public function testSystemActionTest()
    {
        $this->_testUseForm('system', 'System');
    }

    /**
     * Base tests for all _useform()-based actions
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseForm($action, $formClass)
    {
        $form = $this->getMock("Form_Preferences_$formClass");
        $form->expects($this->once())
             ->method('loadDefaults');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->dispatch("/console/preferences/$action");
        $this->assertResponseStatusCode(200);

        $postData = array('key' => 'value');
        $form = $this->getMock("Form_Preferences_$formClass");
        $form->expects($this->once())
             ->method('process')
             ->with($postData);
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->dispatch("/console/preferences/$action", 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    /**
     * Tests for customfieldsAction()
     */
    public function testCustomfieldsAction()
    {
        $url = '/console/preferences/customfields';

        // GET request should render form
        $form = $this->getMock('Form_DefineFields');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('toHtml');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage custom fields\n");

        // POST request with invalid data should render form
        $postData = array('key' => 'value');
        $form = $this->getMock('Form_DefineFields');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->once())
             ->method('toHtml');
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch($url, 'POST', $postData);
        $this->assertResponseStatusCode(200);

        // POST request with valid data should process and redirect
        $form = $this->getMock('Form_DefineFields');
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch($url, 'POST', $postData);
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    /**
     * Tests for deletefieldAction()
     */
    public function testDeletefieldAction()
    {
        $url = '/console/preferences/deletefield/?name=Name';

        // GET request should render form
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertContains("'Name'", $this->getResponse()->getContent());

        // Cancelled POST request should only redirect
        $this->_customFields->expects($this->never())
                            ->method('deleteField');
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/customfields/');

        // Confirmed POST request should delete field and redirect
        $this->_customFields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $this->_customFields->expects($this->once())
                            ->method('deleteField')
                            ->with('Name');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    /**
     * Tests for networkdevicesAction()
     */
    public function testNetworkdevicesAction()
    {
        $url = '/console/preferences/networkdevices';

        // GET request should render form
        $form = $this->getMock('Form_ManageNetworkDeviceTypes');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('toHtml');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage device types\n");

        // POST request with invalid data should render form
        $postData = array('key' => 'value');
        $form = $this->getMock('Form_ManageNetworkDeviceTypes');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->once())
             ->method('toHtml');
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch($url, 'POST', $postData);
        $this->assertResponseStatusCode(200);

        // POST request with valid data should process and redirect
        $form = $this->getMock('Form_ManageNetworkDeviceTypes');
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch($url, 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');
    }

    /**
     * Tests for deletedevicetypeAction()
     */
    public function testDeletedevicetypeAction()
    {
        $url = '/console/preferences/deletedevicetype/?id=1';

        // GET request should render form
        $this->_deviceType->expects($this->any())
                          ->method('fetchById')
                          ->with('1')
                          ->will($this->returnValue(array('Description' => 'description')));
        $this->_deviceType->expects($this->never())
                          ->method('delete');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertContains("'description'", $this->getResponse()->getContent());

        // Cancelled POST request should only redirect
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');

        // Confirmed POST request should delete field and redirect
        $this->_deviceType = $this->getMock('Model_NetworkDeviceType');
        $this->_deviceType->expects($this->any())
                          ->method('fetchById')
                          ->will($this->returnSelf());
        $this->_deviceType->expects($this->once())
                          ->method('delete');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    /**
     * Tests for networkdevicesAction()
     */
    public function testRegistryValuesAction()
    {
        $url = '/console/preferences/registryvalues/';

        // GET request should render form
        $form = $this->getMock('Console\Form\ManageRegistryValues');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('render');
        $formManager = $this->getMock('Zend\Form\FormElementManager');
        $formManager->expects($this->once())
                    ->method('get')
                    ->with('Console\Form\ManageRegistryValues')
                    ->will($this->returnValue($form));
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('FormElementManager')
                           ->will($this->returnValue($formManager));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);

        // POST request with invalid data should render form
        $postData = array('key' => 'value');
        $form = $this->getMock('Console\Form\ManageRegistryValues');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->once())
             ->method('render');
        $formManager = $this->getMock('Zend\Form\FormElementManager');
        $formManager->expects($this->once())
                    ->method('get')
                    ->with('Console\Form\ManageRegistryValues')
                    ->will($this->returnValue($form));
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('FormElementManager')
                           ->will($this->returnValue($formManager));
        $this->dispatch($url, 'POST', $postData);
        $this->assertResponseStatusCode(200);

        // POST request with valid data should process and redirect
        $form = $this->getMock('Console\Form\ManageRegistryValues');
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->never())
             ->method('render');
        $formManager = $this->getMock('Zend\Form\FormElementManager');
        $formManager->expects($this->once())
                    ->method('get')
                    ->with('Console\Form\ManageRegistryValues')
                    ->will($this->returnValue($form));
        $this->_formManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('FormElementManager')
                           ->will($this->returnValue($formManager));
        $this->dispatch($url, 'POST', $postData);
        $this->assertRedirectTo($url);
    }

    /**
     * Tests for deleteregistryvalueAction()
     */
    public function testDeleteregistryvalueAction()
    {
        $url = '/console/preferences/deleteregistryvalue/?id=1';

        // GET request should render form
        $this->_registryValue->expects($this->any())
                             ->method('fetchById')
                             ->with('1')
                             ->will($this->returnValue(array('Name' => 'name')));
        $this->_registryValue->expects($this->never())
                             ->method('delete');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertContains("'name'", $this->getResponse()->getContent());

        // Cancelled POST request should only redirect
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');

        // Confirmed POST request should delete field and redirect
        $this->_registryValue = $this->getMock('Model_RegistryValue');
        $this->_registryValue->expects($this->any())
                             ->method('fetchById')
                             ->will($this->returnSelf());
        $this->_registryValue->expects($this->once())
                             ->method('delete');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }
}
