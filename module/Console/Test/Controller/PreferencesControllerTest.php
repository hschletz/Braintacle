<?php

/**
 * Tests for PreferencesController
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

use Console\Form\DefineFields;
use Console\Form\ManageRegistryValues;
use Console\Form\NetworkDeviceTypes;
use Console\Form\Preferences\Packages;
use Console\View\Helper\Form\ManageRegistryValues as FormManageRegistryValues;
use Laminas\Form\FormElementManager;
use Model\Client\CustomFieldManager;
use Model\Config;
use Model\Network\DeviceManager;
use Model\Registry\RegistryManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PreferencesController
 */
class PreferencesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * @var MockObject|FormElementManager
     */
    protected $_formManager;

    /**
     * @var MockObject|CustomFieldManager
     */
    protected $_customFieldManager;

    /**
     * @var MockObject|DeviceManager
     */
    protected $_deviceManager;

    /**
     * @var MockObject|RegistryManager
     */
    protected $_registryManager;

    /**
     * @var MockObject|Config
     */
    protected $_config;

    /**
     * Set up mock objects
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_formManager = $this->createMock('Laminas\Form\FormElementManager');
        $this->_customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $this->_deviceManager = $this->createMock('Model\Network\DeviceManager');
        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_config = $this->createMock('Model\Config');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('FormElementManager', $this->_formManager);
        $serviceManager->setService('Model\Client\CustomFieldManager', $this->_customFieldManager);
        $serviceManager->setService('Model\Network\DeviceManager', $this->_deviceManager);
        $serviceManager->setService('Model\Registry\RegistryManager', $this->_registryManager);
        $serviceManager->setService('Model\Config', $this->_config);
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
        $this->testUseFormGet('display', 'Display');
    }

    public function testDisplayActionPostInvalid()
    {
        $this->testUseFormPostInvalid('display', 'Display');
    }

    public function testDisplayActionPostValid()
    {
        $this->testUseFormPostValid('display', 'Display');
    }

    public function testInventoryActionGet()
    {
        $this->testUseFormGet('inventory', 'Inventory');
    }

    public function testInventoryActionPostInvalid()
    {
        $this->testUseFormPostInvalid('inventory', 'Inventory');
    }

    public function testInventoryActionPostValid()
    {
        $this->testUseFormPostValid('inventory', 'Inventory');
    }

    public function testAgentActionGet()
    {
        $this->testUseFormGet('agent', 'Agent');
    }

    public function testAgentActionPostInvalid()
    {
        $this->testUseFormPostInvalid('agent', 'Agent');
    }

    public function testAgentActionPostValid()
    {
        $this->testUseFormPostValid('agent', 'Agent');
    }

    public function testPackagesActionGet()
    {
        $deploy = new \Laminas\Form\Fieldset('Deploy');
        $deploy->add(new \Laminas\Form\Element\Text('pref1'));
        $preferences = new \Laminas\Form\Fieldset('Preferences');
        $preferences->add($deploy);
        $preferences->add(new \Laminas\Form\Element\Text('pref2'));
        $formData = array(
            'Preferences' => array(
                'Deploy' => array('pref1' => 'value1'),
                'pref2' => 'value2',
            ),
        );
        $form = $this->createMock(Packages::class);
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
        $this->testUseFormPostInvalid('packages', 'Packages');
    }

    public function testPackagesActionPostValid()
    {
        $postData = array(
            'Preferences' => array(
                'Deploy' => array('pref1' => 'value1'),
                'pref2' => 'value2',
            )
        );
        $form = $this->createMock(Packages::class);
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
                          $this->callback(function ($options) {
                              $options = iterator_to_array($options);
                              return ($options == array('pref1' => 'value1', 'pref2' => 'value2'));
                          })
                      );
        $this->dispatch("/console/preferences/packages", 'POST', $postData);
        $this->assertRedirectTo("/console/preferences/packages/");
    }

    public function testDownloadActionGet()
    {
        $this->testUseFormGet('download', 'Download');
    }

    public function testDownloadActionPostInvalid()
    {
        $this->testUseFormPostInvalid('download', 'Download');
    }

    public function testDownloadActionPostValid()
    {
        $this->testUseFormPostValid('download', 'Download');
    }

    public function testNetworkscanningActionGet()
    {
        $this->testUseFormGet('networkscanning', 'NetworkScanning');
    }

    public function testNetworkscanningActionPostInvalid()
    {
        $this->testUseFormPostInvalid('networkscanning', 'NetworkScanning');
    }

    public function testNetworkscanningActionPostValid()
    {
        $this->testUseFormPostValid('networkscanning', 'NetworkScanning');
    }

    public function testGroupsActionGet()
    {
        $this->testUseFormGet('groups', 'Groups');
    }

    public function testGroupsActionPostInvalid()
    {
        $this->testUseFormPostInvalid('groups', 'Groups');
    }

    public function testGroupsActionPostValid()
    {
        $this->testUseFormPostValid('groups', 'Groups');
    }

    public function testRawdataActionGet()
    {
        $this->testUseFormGet('rawdata', 'RawData');
    }

    public function testRawdataActionPostInvalid()
    {
        $this->testUseFormPostInvalid('rawdata', 'RawData');
    }

    public function testRawdataActionPostValid()
    {
        $this->testUseFormPostValid('rawdata', 'RawData');
    }

    public function testFiltersActionGet()
    {
        $this->testUseFormGet('filters', 'Filters');
    }

    public function testFiltersActionPostInvalid()
    {
        $this->testUseFormPostInvalid('filters', 'Filters');
    }

    public function testFiltersActionPostValid()
    {
        $this->testUseFormPostValid('filters', 'Filters');
    }

    public function testSystemActionGet()
    {
        $this->testUseFormGet('system', 'System');
    }

    public function testSystemActionPostInvalid()
    {
        $this->testUseFormPostInvalid('system', 'System');
    }

    public function testSystemActionPostValid()
    {
        $this->testUseFormPostValid('system', 'System');
    }

    /**
     * Base tests for all _useform()-based actions (GET method)
     *
     * @param string $action "action" part of URI
     * @param string $formClass Form name without namespace
     */
    protected function testUseFormGet($action, $formClass)
    {
        $preferences = new \Laminas\Form\Fieldset('Preferences');
        $preferences->add(new \Laminas\Form\Element\Text('pref1'));
        $preferences->add(new \Laminas\Form\Element\Text('pref2'));
        $formData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->createMock("Console\Form\Preferences\\$formClass");
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
    protected function testUseFormPostInvalid($action, $formClass)
    {
        $postData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->createMock("Console\Form\Preferences\\$formClass");
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
    protected function testUseFormPostValid($action, $formClass)
    {
        $postData = array(
            'Preferences' => array('pref1' => 'value1', 'pref2' => 'value2')
        );
        $form = $this->createMock("Console\Form\Preferences\\$formClass");
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
                          $this->callback(function ($options) {
                              $options = iterator_to_array($options);
                              return ($options == array('pref1' => 'value1', 'pref2' => 'value2'));
                          })
                      );
        $this->dispatch("/console/preferences/$action", 'POST', $postData);
        $this->assertRedirectTo("/console/preferences/$action/");
    }

    public function testCustomfieldsActionGet()
    {
        $form = $this->createMock(DefineFields::class);
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
        $this->assertQueryContentContains('h1', "\nBenutzerdefinierte Felder verwalten\n");
        $this->assertXPathQuery('//form');
    }

    public function testCustomfieldsActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->createMock(DefineFields::class);
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
        $form = $this->createMock(DefineFields::class);
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
        $this->_customFieldManager->expects($this->never())->method('deleteField');
        $this->dispatch('/console/preferences/deletefield/?name=Name');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString("'Name'", $this->getResponse()->getContent());
    }

    public function testDeletefieldActionPostNo()
    {
        $this->_customFieldManager->expects($this->never())->method('deleteField');
        $this->dispatch('/console/preferences/deletefield/?name=Name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    public function testDeletefieldActionPostYes()
    {
        $this->_customFieldManager->expects($this->once())->method('deleteField')->with('Name');
        $this->dispatch('/console/preferences/deletefield/?name=Name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/customfields/');
    }

    public function testNetworkdevicesActionGet()
    {
        $form = $this->createMock(NetworkDeviceTypes::class);
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
        $this->assertQueryContentContains('h1', "\nGerätetypen verwalten\n");
        $this->assertXPathQuery('//form');
    }

    public function testNetworkdevicesActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->createMock(NetworkDeviceTypes::class);
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
        $form = $this->createMock(NetworkDeviceTypes::class);
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
        $this->_deviceManager->expects($this->never())->method('deleteType');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString("'test'", $this->getResponse()->getContent());
    }

    public function testDeletedevicetypeActionPostNo()
    {
        $this->_deviceManager->expects($this->never())->method('deleteType');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    public function testDeletedevicetypeActionPostYes()
    {
        $this->_deviceManager->expects($this->once())->method('deleteType')->with('test');
        $this->dispatch('/console/preferences/deletedevicetype/?name=test', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/networkdevices/');
    }

    public function testRegistryValuesActionGet()
    {
        $form = $this->createMock(ManageRegistryValues::class);
        $form->expects($this->never())
             ->method('process');

        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));

        $formHelper = $this->createMock(FormManageRegistryValues::class);
        $formHelper->expects($this->once())->method('__invoke')->with($form);
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormManageRegistryValues', $formHelper);

        $this->dispatch('/console/preferences/registryvalues/');
        $this->assertResponseStatusCode(200);
    }

    public function testRegistryValuesActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->createMock('Console\Form\ManageRegistryValues');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));

        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));

        $formHelper = $this->createMock(FormManageRegistryValues::class);
        $formHelper->expects($this->once())->method('__invoke')->with($form);
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormManageRegistryValues', $formHelper);

        $this->dispatch('/console/preferences/registryvalues/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testRegistryValuesActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->createMock(ManageRegistryValues::class);
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));

        $this->_formManager->expects($this->once())
                           ->method('get')
                           ->with('Console\Form\ManageRegistryValues')
                           ->will($this->returnValue($form));
        $this->dispatch('/console/preferences/registryvalues/', 'POST', $postData);
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }

    public function testDeleteregistryvalueActionGet()
    {
        $this->_registryManager->expects($this->never())->method('deleteValueDefinition');
        $this->dispatch('/console/preferences/deleteregistryvalue/?name=value_name');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p',
            "Der Registry-Wert 'value_name' wird aus dem Inventar gelöscht. Fortfahren?"
        );
    }

    public function testDeleteregistryvalueActionPostNo()
    {
        $this->_registryManager->expects($this->never())->method('deleteValueDefinition');
        $this->dispatch('/console/preferences/deleteregistryvalue/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }

    public function testDeleteregistryvalueActionPostYes()
    {
        $this->_registryManager->expects($this->once())->method('deleteValueDefinition')->with('value_name');
        $this->dispatch('/console/preferences/deleteregistryvalue/?name=value_name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/preferences/registryvalues/');
    }
}
