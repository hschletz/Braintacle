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
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_formManager = $this->getMock('Zend\Form\FormElementManager');
        $this->_customFields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $this->_deviceType = $this->getMock('Model_NetworkDeviceType');
        $this->_registryValue = $this->getMock('Model_RegistryValue');
        $this->_config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\PreferencesController(
            $this->_formManager,
            $this->_customFields,
            $this->_deviceType,
            $this->_registryValue,
            $this->_config
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

    public function testDisplayActionGet()
    {
        $this->_testUseFormGet('display', 'Display');
    }

    public function testDisplayActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('display', 'Display');
    }

    public function testDisplayActionPostValid()
    {
        $this->_testUseFormPostValid('display', 'Display');
    }

    public function testInventoryActionGet()
    {
        $this->_testUseFormGet('inventory', 'Inventory');
    }

    public function testInventoryActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('inventory', 'Inventory');
    }

    public function testInventoryActionPostValid()
    {
        $this->_testUseFormPostValid('inventory', 'Inventory');
    }

    public function testAgentActionGet()
    {
        $this->_testUseFormGet('agent', 'Agent');
    }

    public function testAgentActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('agent', 'Agent');
    }

    public function testAgentActionPostValid()
    {
        $this->_testUseFormPostValid('agent', 'Agent');
    }

    public function testPackagesActionGet()
    {
        $deploy = new \Zend\Form\Fieldset('Deploy');
        $deploy->add(new \Zend\Form\Element\Text('pref1'));
        $preferences = new \Zend\Form\Fieldset('Preferences');
        $preferences->add($deploy);
        $preferences->add(new \Zend\Form\Element\Text('pref2'));
        $formData = array(
            'Preferences' => array(
                'Deploy' => array('pref1' => 'value1'),
                'pref2' => 'value2',
            ),
        );
        $form = $this->getMock("Console\Form\Preferences\Packages");
        $form->method('get')
             ->willReturn($preferences);
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->once())
             ->method('render')
             ->willReturn('<form></form>');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\Packages")
                           ->will($this->returnValue($form));
        $this->_config->method('__get')
                      ->will($this->returnValueMap(array(array('pref1', 'value1'), array('pref2', 'value2'))));
        $this->_config->expects($this->never())
                      ->method('setOptions');
        $this->dispatch("/console/preferences/packages");
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testPackagesActionPostInvalid()
    {
        // No special test because form data is not evaluated in this test
        $this->_testUseFormPostInvalid('packages', 'Packages');
    }

    public function testPackagesActionPostValid()
    {
        $postData = array(
            'Preferences' => array(
                'Deploy' => array('pref1' => 'value1'),
                'pref2' => 'value2',
            )
        );
        $form = $this->getMock("Console\Form\Preferences\Packages");
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('getData')
             ->willReturn($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->willReturn(true);
        $form->expects($this->never())
             ->method('render');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\Packages")
                           ->will($this->returnValue($form));
        $this->_config->expects($this->once())
                      ->method('setOptions')
                      ->with(
                          $this->callback(
                              function($options) {
                                    $options = iterator_to_array($options);
                                    return ($options == array('pref1' => 'value1', 'pref2' => 'value2'));
                              }
                          )
                      );
        $this->dispatch("/console/preferences/packages", 'POST', $postData);
        $this->assertRedirectTo("/console/preferences/packages/");
    }

    public function testDownloadActionGet()
    {
        $this->_testUseFormGet('download', 'Download');
    }

    public function testDownloadActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('download', 'Download');
    }

    public function testDownloadActionPostValid()
    {
        $this->_testUseFormPostValid('download', 'Download');
    }

    public function testNetworkscanningActionGet()
    {
        $this->_testUseFormGet('networkscanning', 'NetworkScanning');
    }

    public function testNetworkscanningActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('networkscanning', 'NetworkScanning');
    }

    public function testNetworkscanningActionPostValid()
    {
        $this->_testUseFormPostValid('networkscanning', 'NetworkScanning');
    }

    public function testGroupsActionGet()
    {
        $this->_testUseFormGet('groups', 'Groups');
    }

    public function testGroupsActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('groups', 'Groups');
    }

    public function testGroupsActionPostValid()
    {
        $this->_testUseFormPostValid('groups', 'Groups');
    }

    public function testRawdataActionGet()
    {
        $this->_testUseFormGet('rawdata', 'RawData');
    }

    public function testRawdataActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('rawdata', 'RawData');
    }

    public function testRawdataActionPostValid()
    {
        $this->_testUseFormPostValid('rawdata', 'RawData');
    }

    public function testFiltersActionGet()
    {
        $this->_testUseFormGet('filters', 'Filters');
    }

    public function testFiltersActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('filters', 'Filters');
    }

    public function testFiltersActionPostValid()
    {
        $this->_testUseFormPostValid('filters', 'Filters');
    }

    public function testSystemActionGet()
    {
        $this->_testUseFormGet('system', 'System');
    }

    public function testSystemActionPostInvalid()
    {
        $this->_testUseFormPostInvalid('system', 'System');
    }

    public function testSystemActionPostValid()
    {
        $this->_testUseFormPostValid('system', 'System');
    }

    /**
     * Base tests for all _useform()-based actions (GET method)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseFormGet($action, $formClass)
    {
        $preferences = new \Zend\Form\Fieldset('Preferences');
        $preferences->add(new \Zend\Form\Element\Text('pref1'));
        $preferences->add(new \Zend\Form\Element\Text('pref2'));
        $formData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->getMock("Console\Form\Preferences\\$formClass");
        $form->method('get')
             ->willReturn($preferences);
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->once())
             ->method('render')
             ->willReturn('<form></form>');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->_config->method('__get')
                      ->will($this->returnValueMap(array(array('pref1', 'value1'), array('pref2', 'value2'))));
        $this->_config->expects($this->never())
                      ->method('setOptions');
        $this->dispatch("/console/preferences/$action");
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    /**
     * Base tests for all _useform()-based actions (POST method, invalid data)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseFormPostInvalid($action, $formClass)
    {
        $postData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->getMock("Console\Form\Preferences\\$formClass");
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->once())
             ->method('isValid')
             ->willReturn(false);
        $form->expects($this->once())
             ->method('render')
             ->willReturn('<form></form>');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->_config->expects($this->never())
                      ->method('setOptions');
        $this->dispatch("/console/preferences/$action", 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    /**
     * Base tests for all _useform()-based actions (POST method, valid data)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function _testUseFormPostValid($action, $formClass)
    {
        $postData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->getMock("Console\Form\Preferences\\$formClass");
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('getData')
             ->willReturn($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->willReturn(true);
        $form->expects($this->never())
             ->method('render');
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with("Console\Form\Preferences\\$formClass")
                           ->will($this->returnValue($form));
        $this->_config->expects($this->once())
                      ->method('setOptions')
                      ->with(
                          $this->callback(
                              function($options) {
                                    $options = iterator_to_array($options);
                                    return ($options == array('pref1' => 'value1', 'pref2' => 'value2'));
                              }
                          )
                      );
        $this->dispatch("/console/preferences/$action", 'POST', $postData);
        $this->assertRedirectTo("/console/preferences/$action/");
    }

    public function testCustomfieldsActionGet()
    {
        $form = $this->getMock('Console\Form\DefineFields');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form'));
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/customfields');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage custom fields\n");
        $this->assertXPathQuery('//form');
    }

    public function testCustomfieldsActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Console\Form\DefineFields');
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
             ->will($this->returnValue('<form></form'));
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\DefineFields')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/customfields', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testCustomfieldsActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Console\Form\DefineFields');
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
        $this->_formManager->expects($this->once())
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
        $form = $this->getMock('Console\Form\NetworkDeviceTypes');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('h1', "\nManage device types\n");
        $this->assertXPathQuery('//form');
    }

    public function testNetworkdevicesActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Console\Form\NetworkDeviceTypes');
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
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testNetworkdevicesActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->getMock('Console\Form\NetworkDeviceTypes');
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
        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\NetworkDeviceTypes')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/networkdevices', 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testDeletedevicetypeActionGet()
    {
        $this->_deviceType->expects($this->any())
                          ->method('fetchByName')
                          ->with('test')
                          ->will($this->returnValue(array('Description' => 'description')));
        $this->_deviceType->expects($this->never())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'description'", $this->getResponse()->getContent());
    }

    public function testDeletedevicetypeActionPostNo()
    {
        $this->_deviceType->expects($this->never())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    public function testDeletedevicetypeActionPostYes()
    {
        $this->_deviceType->expects($this->any())
                          ->method('fetchByName')
                          ->with('test')
                          ->will($this->returnSelf());
        $this->_deviceType->expects($this->once())
                          ->method('delete');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test', 'POST', array('yes' => 'Yes'));
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
