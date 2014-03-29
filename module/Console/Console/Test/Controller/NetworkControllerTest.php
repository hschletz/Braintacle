<?php
/**
 * Tests for NetworkController
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
 * Tests for NetworkController
 */
class NetworkControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Device mock
     * @var \Model_NetworkDevice
     */
    protected $_device;

    /**
     * DeviceType mock
     * @var \Model_NetworkDeviceType
     */
    protected $_deviceType;

    /**
     * Subnet mock
     * @var \Model_Subnet
     */
    protected $_subnet;

    /**
     * Subnet form mock
     * @var \Form_Subnet
     */
    protected $_subnetForm;

    /**
     * Device form mock
     * @var \Form_NetworkDevice
     */
    protected $_deviceForm;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_deviceType = $this->getMock('Model_NetworkDeviceType');
        $this->_subnet = $this->getMock('Model_Subnet');
        $this->_subnetForm = $this->getMock('Form_Subnet');
        $this->_deviceForm = $this->getMock('Form_NetworkDevice');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\NetworkController(
            $this->_device,
            $this->_deviceType,
            $this->_subnet,
            $this->_subnetForm,
            $this->_deviceForm
        );
    }

    /** {@inheritdoc} */
    public function testService()
    {
        $this->_overrideService('Console\Form\NetworkDevice', $this->_deviceForm);
        parent::testService();
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/network/index/';
        $devices = array(
            array('Description' => 'type1', 'Count' => 0),
            array('Description' => 'type2', 'Count' => 1),
        );
        $subnets = array(
            array(
                'Name' => 'subnet1',
                'Address' => '192.0.2.0',
                'Mask' => '255.255.255.0',
                'AddressWithMask' => '192.0.2.0/24',
                'NumInventoried' => 1,
                'NumIdentified' => 0,
                'NumUnknown' => 0,
            ),
            array(
                'Name' => null,
                'Address' => '198.51.100.0',
                'Mask' => '255.255.255.0',
                'AddressWithMask' => '198.51.100.0/24',
                'NumInventoried' => 1,
                'NumIdentified' => 2,
                'NumUnknown' => 3,
            ),
        );

        $this->_deviceType->expects($this->once())
                          ->method('fetchAll')
                          ->will($this->returnValue($devices));

        $this->_subnet->expects($this->once())
                      ->method('fetchAll')
                      ->will($this->returnValue($subnets));

        $this->dispatch($url);
        $this->assertResponseStatusCode(200);

        // Network device section
        $this->assertQueryContentContains('h2', "\nIdentified network devices\n");
        $this->assertQueryContentContains('td', "\ntype1\n");
        $this->assertQueryContentContains('td', "\n0\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/showidentified/?type=type2"]',
            "\n1\n"
        );
        $this->assertQueryContentContains(
            'p a[href="/console/preferences/networkdevices/"]',
            'Manage device types'
        );

        // Subnet section
        $this->assertQueryContentContains('h2', "\nSubnets\n");

        // First row: named subnet, no uninventoried devices
        $this->assertQueryContentContains(
            'td a[href="/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0"]',
            'subnet1'
        );
        $this->assertNotQueryContentContains('td a[class="gray"]', 'subnet1');
        $this->assertQueryContentContains('td', "\n192.0.2.0/24\n"); // AddressWithMask column
        $this->assertQueryContentContains(
            'td a[href*="/console/computer/index/"][href*="search1=192.0.2.0"][href*="search2=255.255.255.0"]',
            '1'
        );
        $this->assertNotQuery('td a[href="/console/network/showidentified/?subnet=192.0.2.0&mask=255.255.255.0"]');
        $this->assertNotQuery('td a[href="/console/network/showunknown/?subnet=192.0.2.0&mask=255.255.255.0"]');

        // Second row: unnamed subnet with uninventoried devices
        // AddressWithMask and NumInventoried columns are not tested again
        $this->assertQueryContentContains(
            'td a[href="/console/network/properties/?subnet=198.51.100.0&mask=255.255.255.0"][class="gray"]',
            'Edit'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/showidentified/?subnet=198.51.100.0&mask=255.255.255.0"]',
            "\n2\n"
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/showunknown/?subnet=198.51.100.0&mask=255.255.255.0"]',
            "\n3\n"
        );
    }

    /**
     * Tests for showidentifiedAction()
     */
    public function testShowidentifiedAction()
    {
        $url = '/console/network/showidentified/';
        $filters = array('Identified' => true);
        $result = array(
            array(
                'MacAddress' => '00:00:5E:00:53:00',
                'IpAddress' => '192.0.2.1',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
                'Type' => 'type',
                'Description' => 'description',
                'IdentifiedBy' => 'idendified_by',
            ),
        );

        $this->_device->expects($this->once())
                      ->method('fetch')
                      ->with($filters, 'DiscoveryDate', 'desc')
                      ->will($this->returnValue($result));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n");
        $this->assertQueryContentContains('td', "\n192.0.2.1\n");
        $this->assertQueryContentContains('td', "\n23.02.14 18:43\n");
        $this->assertQueryContentContains('td', "\ntype\n");
        $this->assertQueryContentContains('td', "\ndescription\n");
        $this->assertQueryContentContains('td', "\nidendified_by\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/edit/?macaddress=00:00:5E:00:53:00"]',
            'Edit'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/delete/?macaddress=00:00:5E:00:53:00"]',
            'Delete'
        );

        // Test evaluation of optional URL parameters
        $filters += array(
            'Subnet' => '192.0.2.0',
            'Mask' => '255.255.255.0',
            'Type' => 'type'
        );
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetch')
                      ->with($filters, 'DiscoveryDate', 'desc')
                      ->will($this->returnValue($result));
        $this->dispatch($url . '?subnet=192.0.2.0&mask=255.255.255.0&type=type');
    }

    /**
     * Tests for showunknownAction()
     */
    public function testShowunknownAction()
    {
        $url = '/console/network/showunknown/';
        $filters = array('Identified' => false);
        $result = array(
            array(
                'MacAddress' => '00:00:5E:00:53:00',
                'Vendor' => 'vendor',
                'IpAddress' => '192.0.2.1',
                'Hostname' => 'host.example.net',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
            ),
        );

        $this->_device->expects($this->once())
                      ->method('fetch')
                      ->with($filters, 'DiscoveryDate', 'desc')
                      ->will($this->returnValue($result));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n");
        $this->assertQueryContentContains('td', "\nvendor\n");
        $this->assertQueryContentContains('td', "\n192.0.2.1\n");
        $this->assertQueryContentContains('td', "\nhost.example.net\n");
        $this->assertQueryContentContains('td', "\n23.02.14 18:43\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/edit/?macaddress=00:00:5E:00:53:00"]',
            'Edit'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/delete/?macaddress=00:00:5E:00:53:00"]',
            'Delete'
        );

        // Test evaluation of optional URL parameters
        $filters += array(
            'Subnet' => '192.0.2.0',
            'Mask' => '255.255.255.0',
        );
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetch')
                      ->with($filters, 'DiscoveryDate', 'desc')
                      ->will($this->returnValue($result));
        $this->dispatch($url . '?subnet=192.0.2.0&mask=255.255.255.0&type=type');
    }

    /**
     * Tests for propertiesAction()
     */
    public function testPropertiesAction()
    {
        $url = '/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0';
        $subnet = array(
            'Address' => '192.0.2.0',
            'Mask' => '255.255.255.0',
            'AddressWithMask' => '192.0.2.0/24',
            'Name' => 'name',
        );
        $this->_subnet->expects($this->any())
                      ->method('create')
                      ->with('192.0.2.0', '255.255.255.0')
                      ->will($this->returnValue($subnet));

        // Test GET method
        $this->_subnetForm->expects($this->once())
                          ->method('setDefault')
                          ->with('Name', 'name');
        $this->_subnetForm->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue(''));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'h1',
            "\nProperties of subnet 192.0.2.0/24\n"
        );

        // Test POST method with invalid data
        $this->_subnetForm = $this->getMock('Form_Subnet');
        $this->_subnetForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(false));
        $this->_subnetForm->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue(''));
        $this->dispatch($url, 'POST');
        $this->assertResponseStatusCode(200);

        // Test POST method with valid data
        $postData = array('Name' => 'new_name');
        $this->_subnetForm = $this->getMock('Form_Subnet');
        $this->_subnetForm->expects($this->once())
                          ->method('isValid')
                          ->with($postData)
                          ->will($this->returnValue(true));
        $this->_subnetForm->expects($this->once())
                          ->method('getValue')
                          ->with('Name')
                          ->will($this->returnValue('new_name'));
        $this->dispatch($url, 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');

        // Test invalid (missing) query params
        $this->_subnet = $this->getMockBuilder('Model_Subnet')->setMethods(null)->getMock();
        $this->setExpectedException('InvalidArgumentException');
        $this->dispatch('/console/network/properties');
    }

    /**
     * Tests for editAction()
     */
    public function testEditAction()
    {
        $url = '/console/network/edit/?macaddress=00:00:5E:00:53:00';
        $macAddress = $this->getMockBuilder('Braintacle_MacAddress')->disableOriginalConstructor()->getMock();
        $macAddress->expects($this->any())
                   ->method('__toString')
                   ->will($this->returnValue('00:00:5E:00:53:00'));
        $macAddress->expects($this->any())
                   ->method('getVendor')
                   ->will($this->returnValue('vendor'));
        $device = array(
            'MacAddress' => $macAddress,
            'IpAddress' => '192.0.2.1',
            'Hostname' => 'host.example.net',
            'DiscoveryDate' => new \Zend_Date('2014-02-24 13:21:32'),
        );
        $this->_device->expects($this->any())
                      ->method('fetchByMacAddress')
                      ->with('00:00:5E:00:53:00')
                      ->will($this->returnValue($device));

        // Test GET method
        $this->_deviceForm->expects($this->exactly(4))
                          ->method('setDefault')
                          ->with(
                              $this->callback(
                                  function($arg) use($device) {
                                      return isset($device[$arg]);
                                  }
                              ),
                              $this->callback(
                                  function($arg) use($device) {
                                      return in_array($arg, $device);
                                  }
                              )
                          );
        $this->_deviceForm->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue(''));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'dd',
            "\n00:00:5E:00:53:00\n"
        );
        $this->assertQueryContentContains(
            'dd',
            "\nvendor\n"
        );
        $this->assertQueryContentContains(
            'dd',
            "\n192.0.2.1\n"
        );
        $this->assertQueryContentContains(
            'dd',
            "\nhost.example.net\n"
        );
        $this->assertQueryContentContains(
            'dd',
            "\n24.02.2014 13:21:32\n"
        );

        // Test POST method with invalid data
        $this->_deviceForm = $this->getMock('Form_NetworkDevice');
        $this->_deviceForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(false));
        $this->_deviceForm->expects($this->once())
                          ->method('__toString')
                          ->will($this->returnValue(''));
        $this->dispatch($url, 'POST');
        $this->assertResponseStatusCode(200);

        // Test POST method with valid data
        $postData = array('Type' => 'type', 'Description' => 'description');
        $this->_deviceForm = $this->getMock('Form_NetworkDevice');
        $this->_deviceForm->expects($this->once())
                          ->method('isValid')
                          ->with($postData)
                          ->will($this->returnValue(true));
        $this->_deviceForm->expects($this->once())
                          ->method('getValues')
                          ->will($this->returnValue($postData));
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->any())
                      ->method('fetchByMacAddress')
                      ->with('00:00:5E:00:53:00')
                      ->will($this->returnSelf());
        $this->_device->expects($this->once())
                      ->method('fromArray')
                      ->with($postData);
        $this->_device->expects($this->once())
                      ->method('save');
        $this->dispatch($url, 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');

        // Test invalid (missing) query params
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetchByMacAddress')
                      ->with(null)
                      ->will($this->returnValue(false));
        $this->dispatch('/console/network/edit');
        $this->assertRedirectTo('/console/network/index/');
    }

    /**
     * Tests for deleteAction()
     */
    public function testDeleteAction()
    {
        $url = '/console/network/delete/?macaddress=00:00:5E:00:53:00';
        $macAddress = $this->getMockBuilder('Braintacle_MacAddress')->disableOriginalConstructor()->getMock();
        $macAddress->expects($this->any())
                   ->method('__toString')
                   ->will($this->returnValue('00:00:5E:00:53:00'));
        $device = array(
            'MacAddress' => $macAddress,
            'Hostname' => 'host.example.net',
            'Description' => null,
        );
        $this->_device->expects($this->any())
                      ->method('fetchByMacAddress')
                      ->with('00:00:5E:00:53:00')
                      ->will($this->returnValue($device));

        // Test GET method
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertContains('host.example.net', $this->getResponse()->getContent());
        $this->assertContains('00:00:5E:00:53:00', $this->getResponse()->getContent());

        // Test POST method with cancelled form
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetchByMacAddress')
                      ->with('00:00:5E:00:53:00')
                      ->will($this->returnSelf());
        $this->_device->expects($this->never())
                      ->method('delete');
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/network/index/');

        // Test POST method with confirmed form
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetchByMacAddress')
                      ->with('00:00:5E:00:53:00')
                      ->will($this->returnSelf());
        $this->_device->expects($this->once())
                      ->method('delete');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/network/index/');

        // Test invalid (missing) query params
        $this->_device = $this->getMock('Model_NetworkDevice');
        $this->_device->expects($this->once())
                      ->method('fetchByMacAddress')
                      ->with(null)
                      ->will($this->returnValue(false));
        $this->_device->expects($this->never())
                      ->method('delete');
        $this->dispatch('/console/network/delete');
        $this->assertRedirectTo('/console/network/index/');
    }
}
