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
     * @var \Zend\Form\FormElementManager
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
        $this->_legacyFormManager = $this->getMock('Zend\ServiceManager\ServiceManager');
        $this->_formManager = $this->getMock('Zend\Form\FormElementManager');
        $this->_formManager->expects($this->any())
                           ->method('getServiceLocator')
                           ->will($this->returnValue($this->_legacyFormManager));
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

    public function testDisplayActionTestGet()
    {
        $this->_testUseFormGet('display', 'Display');
    }

    public function testDisplayActionTestPost()
    {
        $this->_testUseFormPost('display', 'Display');
    }

    public function testInventoryActionTestGet()
    {
        $this->_testUseFormGet('inventory', 'Inventory');
    }

    public function testInventoryActionTestPost()
    {
        $this->_testUseFormPost('inventory', 'Inventory');
    }

    public function testAgentActionTestGet()
    {
        $this->_testUseFormGet('agent', 'Agent');
    }

    public function testAgentActionTestPost()
    {
        $this->_testUseFormPost('agent', 'Agent');
    }

    public function testPackagesActionTestGet()
    {
        $this->_testUseFormGet('packages', 'Packages');
    }

    public function testPackagesActionTestPost()
    {
        $this->_testUseFormPost('packages', 'Packages');
    }

    public function testDownloadActionTestGet()
    {
        $this->_testUseFormGet('download', 'Download');
    }

    public function testDownloadActionTestPost()
    {
        $this->_testUseFormPost('download', 'Download');
    }

    public function testNetworkscanningActionTestGet()
    {
        $this->_testUseFormGet('networkscanning', 'NetworkScanning');
    }

    public function testNetworkscanningActionTestPost()
    {
        $this->_testUseFormPost('networkscanning', 'NetworkScanning');
    }

    public function testGroupsActionTestGet()
    {
        $this->_testUseFormGet('groups', 'Groups');
    }

    public function testGroupsActionTestPost()
    {
        $this->_testUseFormPost('groups', 'Groups');
    }

    public function testRawdataActionTestGet()
    {
        $this->_testUseFormGet('rawdata', 'RawData');
    }

    public function testRawdataActionTestPost()
    {
        $this->_testUseFormPost('rawdata', 'RawData');
    }

    public function testFiltersActionTestGet()
    {
        $this->_testUseFormGet('filters', 'Filters');
    }

    public function testFiltersActionTestPost()
    {
        $this->_testUseFormPost('filters', 'Filters');
    }

    public function testSystemActionTestGet()
    {
        $this->_testUseFormGet('system', 'System');
    }

    public function testSystemActionTestPost()
    {
        $this->_testUseFormPost('system', 'System');
    }

    /**
     * Base tests for all _useform()-based actions (GET method)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseFormGet($action, $formClass)
    {
        $form = $this->getMock("Form_Preferences_$formClass");
        $form->expects($this->once())
             ->method('loadDefaults');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->dispatch("/console/preferences/$action");
        $this->assertResponseStatusCode(200);
    }

    /**
     * Base tests for all _useform()-based actions (POST method)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseFormPost($action, $formClass)
    {
        $postData = array('key' => 'value');
        $form = $this->getMock("Form_Preferences_$formClass");
        $form->expects($this->once())
             ->method('process')
             ->with($postData);
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->dispatch("/console/preferences/$action", 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testCustomfieldsActionGet()
    {
        $form = $this->getMock('Form_DefineFields');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('toHtml');
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/customfields');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage custom fields\n");
    }

    public function testCustomfieldsActionPostInvalid()
    {
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
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/customfields', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testCustomfieldsActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Form_DefineFields');
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/customfields', 'POST', $postData);
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    public function testDeletefieldActionGet()
    {
        $this->_customFields->expects($this->never())
                            ->method('deleteField');
        $this->dispatch('/console/preferences/deletefield/?name=Name');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'Name'", $this->getResponse()->getContent());
    }

    public function testDeletefieldActionPostNo()
    {
        $this->_customFields->expects($this->never())
                            ->method('deleteField');
        $this->dispatch('/console/preferences/deletefield/?name=Name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    public function testDeletefieldActionPostYes()
    {
        $this->_customFields->expects($this->once())
                            ->method('deleteField')
                            ->with('Name');
        $this->dispatch('/console/preferences/deletefield/?name=Name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    public function testNetworkdevicesActionGet()
    {
        $form = $this->getMock('Form_ManageNetworkDeviceTypes');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('toHtml');
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage device types\n");
    }

    public function testNetworkdevicesActionPostInvalid()
    {
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
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testNetworkdevicesActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Form_ManageNetworkDeviceTypes');
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $this->_legacyFormManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices', 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testDeletedevicetypeActionGet()
    {
        $this->_deviceType->expects($this->any())
                          ->method('fetchById')
                          ->with('1')
                          ->will($this->returnValue(array('Description' => 'description')));
        $this->_deviceType->expects($this->never())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'description'", $this->getResponse()->getContent());
    }

    public function testDeletedevicetypeActionPostNo()
    {
        $this->_deviceType->expects($this->never())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    public function testDeletedevicetypeActionPostYes()
    {
        $this->_deviceType->expects($this->any())
                          ->method('fetchById')
                          ->will($this->returnSelf());
        $this->_deviceType->expects($this->once())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?id=1', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    public function testRegistryValuesActionGet()
    {
        $form = $this->getMock('Console\Form\ManageRegistryValues');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('render');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/registryvalues/');
        $this->assertResponseStatusCode(200);
    }

    public function testRegistryValuesActionPostInvalid()
    {
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
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/registryvalues/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testRegistryValuesActionPostValid()
    {
        $postData = array('key' => 'value');
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
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/registryvalues/', 'POST', $postData);
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }

    public function testDeleteregistryvalueActionGet()
    {
        $this->_registryValue->expects($this->any())
                             ->method('fetchById')
                             ->with('1')
                             ->will($this->returnValue(array('Name' => 'name')));
        $this->_registryValue->expects($this->never())
                             ->method('delete');
        $this->dispatch('/console/preferences/deleteregistryvalue/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'name'", $this->getResponse()->getContent());
    }

    public function testDeleteregistryvalueActionPostNo()
    {
        $this->_registryValue->expects($this->never())
                             ->method('delete');
        $this->dispatch('/console/preferences/deleteregistryvalue/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }

    public function testDeleteregistryvalueActionPostYes()
    {
        $this->_registryValue->expects($this->any())
                             ->method('fetchById')
                             ->will($this->returnSelf());
        $this->_registryValue->expects($this->once())
                             ->method('delete');
        $this->dispatch('/console/preferences/deleteregistryvalue/?id=1', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }
}
