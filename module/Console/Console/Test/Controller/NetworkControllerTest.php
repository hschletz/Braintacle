<?php
/**
 * Tests for NetworkController
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
 * Tests for NetworkController
 */
class NetworkControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * DeviceManager mock
     * @var \Model\Network\DeviceManager
     */
    protected $_deviceManager;

    /**
     * SubnetManager mock
     * @var \Model\Network\SubnetManager
     */
    protected $_subnetManager;

    /**
     * Subnet form mock
     * @var \Console\Form\Subnet
     */
    protected $_subnetForm;

    /**
     * Device form mock
     * @var \Console\Form\NetworkDevice
     */
    protected $_deviceForm;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_deviceManager = $this->getMockBuilder('Model\Network\DeviceManager')
                                     ->disableOriginalConstructor()
                                     ->getMock();
        $this->_subnetManager = $this->getMockBuilder('Model\Network\SubnetManager')
                                     ->disableOriginalConstructor()
                                     ->getMock();
        $this->_subnetForm = $this->getMock('Console\Form\Subnet');
        $this->_deviceForm = $this->getMock('Console\Form\NetworkDevice');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\NetworkController(
            $this->_deviceManager,
            $this->_subnetManager,
            $this->_subnetForm,
            $this->_deviceForm
        );
    }

    public function testService()
    {
        $this->_overrideService('Console\Form\NetworkDevice', $this->_deviceForm);
        parent::testService();
    }

    public function testIndexAction()
    {
        $devices = array(
            'type1' => 0,
            'type2' => 1,
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
        $this->_deviceManager->expects($this->once())->method('getTypeCounts')->willReturn($devices);
        $this->_subnetManager->expects($this->once())
                             ->method('getSubnets')
                             ->willReturn($subnets);
        $this->dispatch('/console/network/index/');
        $this->assertResponseStatusCode(200);

        // Network device section
        $this->assertQueryContentContains('h2', "\nIdentifizierte Netzwerkgeräte\n");
        $this->assertQueryContentContains('td', "\ntype1\n");
        $this->assertQueryContentContains('td', "\n0\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/showidentified/?type=type2"]',
            "\n1\n"
        );
        $this->assertQueryContentContains(
            'p a[href="/console/preferences/networkdevices/"]',
            'Gerätetypen verwalten'
        );

        // Subnet section
        $this->assertQueryContentContains('h2', "\nSubnetze\n");

        // First row: named subnet, no uninventoried devices
        $this->assertQueryContentContains(
            'td a[href="/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0"]',
            'subnet1'
        );
        $this->assertNotQueryContentContains('td a[class="blur"]', 'subnet1');
        $this->assertQueryContentContains('td', "\n192.0.2.0/24\n"); // AddressWithMask column
        $this->assertQueryContentContains(
            'td a[href*="/console/client/index/"][href*="search1=192.0.2.0"][href*="search2=255.255.255.0"]',
            '1'
        );
        $this->assertNotQuery('td a[href="/console/network/showidentified/?subnet=192.0.2.0&mask=255.255.255.0"]');
        $this->assertNotQuery('td a[href="/console/network/showunknown/?subnet=192.0.2.0&mask=255.255.255.0"]');

        // Second row: unnamed subnet with uninventoried devices
        // AddressWithMask and NumInventoried columns are not tested again
        $this->assertQueryContentContains(
            'td a[href="/console/network/properties/?subnet=198.51.100.0&mask=255.255.255.0"][class="blur"]',
            'Bearbeiten'
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

    public function testShowidentifiedActionWithoutParameters()
    {
        $filters = array('Identified' => true);
        $result = array(
            array(
                'MacAddress' => '00:00:5E:00:53:00',
                'IpAddress' => '192.0.2.1',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
                'Type' => 'type',
                'Description' => 'description',
            ),
        );
        $this->_deviceManager->expects($this->once())
                             ->method('getDevices')
                             ->with($filters, 'DiscoveryDate', 'desc')
                             ->willReturn($result);

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->once())
                   ->method('__invoke')
                   ->with(1393177422, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   ->willReturn('date1');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

        $this->dispatch('/console/network/showidentified/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n");
        $this->assertQueryContentContains('td', "\n192.0.2.1\n");
        $this->assertQueryContentContains('td', "\ndate1\n");
        $this->assertQueryContentContains('td', "\ntype\n");
        $this->assertQueryContentContains('td', "\ndescription\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/edit/?macaddress=00:00:5E:00:53:00"]',
            'Bearbeiten'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/delete/?macaddress=00:00:5E:00:53:00"]',
            'Löschen'
        );
    }

    public function testShowidentifiedActionWithParameters()
    {
        $filters = array(
            'Identified' => true,
            'Subnet' => '192.0.2.0',
            'Mask' => '255.255.255.0',
            'Type' => 'type'
        );
        $result = array(
            array(
                'MacAddress' => '00:00:5E:00:53:00',
                'IpAddress' => '192.0.2.1',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
                'Type' => 'type',
                'Description' => 'description',
            ),
        );
        $this->_deviceManager->expects($this->once())
                             ->method('getDevices')
                             ->with($filters, 'DiscoveryDate', 'desc')
                             ->willReturn($result);
        $this->dispatch('/console/network/showidentified/?subnet=192.0.2.0&mask=255.255.255.0&type=type');
    }

    public function testShowunknownActionWithoutParameters()
    {
        $filters = array('Identified' => false);
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
        $macAddress->method('__toString')->willReturn('00:00:5E:00:53:00');
        $macAddress->method('getVendor')->willReturn('<vendor>');
        $result = array(
            array(
                'MacAddress' => $macAddress,
                'IpAddress' => '192.0.2.1',
                'Hostname' => 'host.example.net',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
            ),
        );
        $this->_deviceManager->expects($this->once())
                             ->method('getDevices')
                             ->with($filters, 'DiscoveryDate', 'desc')
                             ->willReturn($result);

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->once())
                   ->method('__invoke')
                   ->with(1393177422, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   ->willReturn('date1');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

        $this->dispatch('/console/network/showunknown/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n");
        $this->assertQueryContentContains('td', "\n<vendor>\n");
        $this->assertQueryContentContains('td', "\n192.0.2.1\n");
        $this->assertQueryContentContains('td', "\nhost.example.net\n");
        $this->assertQueryContentContains('td', "\ndate1\n");
        $this->assertQueryContentContains(
            'td a[href="/console/network/edit/?macaddress=00:00:5E:00:53:00"]',
            'Bearbeiten'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/network/delete/?macaddress=00:00:5E:00:53:00"]',
            'Löschen'
        );
    }

    public function testShowunknownActionWithParameters()
    {
        $filters = array(
            'Identified' => false,
            'Subnet' => '192.0.2.0',
            'Mask' => '255.255.255.0',
        );
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
        $macAddress->method('__toString')->willReturn('00:00:5E:00:53:00');
        $macAddress->method('getVendor')->willReturn('<vendor>');
        $result = array(
            array(
                'MacAddress' => $macAddress,
                'IpAddress' => '192.0.2.1',
                'Hostname' => 'host.example.net',
                'DiscoveryDate' => new \Zend_Date('2014-02-23 18:43:42'),
            ),
        );
        $this->_deviceManager->expects($this->once())
                             ->method('getDevices')
                             ->with($filters, 'DiscoveryDate', 'desc')
                             ->willReturn($result);
        $this->dispatch('/console/network/showunknown/?subnet=192.0.2.0&mask=255.255.255.0&type=type');
    }

    public function testPropertiesActionGet()
    {
        $map = array(
            array('Address', '192.0.2.0'),
            array('Mask', '255.255.255.0'),
            array('AddressWithMask', '192.0.2.0/24'),
            array('Name', 'name'),
        );
        $subnet = $this->getMock('Model_Subnet');
        $subnet->expects($this->never())->method('offsetSet');
        $subnet->method('offsetGet')->will($this->returnValueMap($map));

        $this->_subnetManager->expects($this->once())
                             ->method('getSubnet')
                             ->with('192.0.2.0', '255.255.255.0')
                             ->willReturn($subnet);

        $this->_subnetForm->expects($this->once())
                          ->method('setData')
                          ->with(array('Name' => 'name'));
        $this->_subnetForm->expects($this->once())
                          ->method('render')
                          ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'h1',
            "\nEigenschaften von Subnetz 192.0.2.0/24\n"
        );
        $this->assertXPathQuery('//form');
    }

    public function testPropertiesActionPostInvalid()
    {
        $postData = array('Name' => 'new_name');

        $subnet = $this->getMock('Model_Subnet');
        $subnet->expects($this->never())->method('offsetSet');

        $this->_subnetManager->expects($this->once())
                             ->method('getSubnet')
                             ->with('192.0.2.0', '255.255.255.0')
                             ->willReturn($subnet);

        $this->_subnetForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_subnetForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(false));
        $this->_subnetForm->expects($this->once())
                          ->method('render');
        $this->dispatch('/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testPropertiesActionPostValid()
    {
        $postData = array('Name' => 'new_name');

        $subnet = $this->getMock('Model_Subnet');
        $subnet->expects($this->once())->method('offsetSet')->with('Name', 'new_name');

        $this->_subnetManager->expects($this->once())
                             ->method('getSubnet')
                             ->with('192.0.2.0', '255.255.255.0')
                             ->willReturn($subnet);

        $this->_subnetForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_subnetForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(true));
        $this->_subnetForm->expects($this->once())
                          ->method('getData')
                          ->will($this->returnValue($postData));
        $this->_subnetForm->expects($this->never())
                          ->method('render');

        $this->dispatch('/console/network/properties/?subnet=192.0.2.0&mask=255.255.255.0', 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testPropertiesActionMissingParams()
    {
        $this->_subnetManager = $this->getMockBuilder('Model\Network\SubnetManager')
                                     ->disableOriginalConstructor()
                                     ->setMethods(null)
                                     ->getMock();
        $this->dispatch('/console/network/properties');
        $this->assertApplicationException('InvalidArgumentException');
    }

    public function testEditActionGet()
    {
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
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
            'Type' => 'type1',
            'Description' => 'description1',
        );
        $this->_deviceManager->method('getDevice')
                             ->with('00:00:5E:00:53:00')
                             ->willReturn($device);
        $this->_deviceManager->expects($this->once())
                             ->method('getTypes')
                             ->willReturn(array('type'));
        // Since form elements are rendered manually, mocking the entire form
        // would be very complicated. Just stub the pivotal methods and leave
        // elements as is.
        $this->_deviceForm = $this->getMockBuilder('Console\Form\NetworkDevice')
                                  ->setMethods(array('isValid', 'prepare', 'setData'))
                                  ->getMock();
        $this->_deviceForm->expects($this->once())
                          ->method('setData')
                          ->with(array('Type' => 'type1', 'Description' => 'description1'));
        $this->_deviceForm->expects($this->never())
                          ->method('isValid');
        $this->_deviceForm->expects($this->once())
                          ->method('prepare');
        $this->_deviceForm->setOption('DeviceManager', $this->_deviceManager);
        $this->_deviceForm->init();
        $this->dispatch('/console/network/edit/?macaddress=00:00:5E:00:53:00');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form[@action=""][@method="POST"]');
        $query = '//td[@class="label"][text()="%s"]/following::td[1][text()="%s"]';
        $this->assertXPathQuery(sprintf($query, 'MAC-Adresse', '00:00:5E:00:53:00'));
        $this->assertXPathQuery(sprintf($query, 'Hersteller', 'vendor'));
        $this->assertXPathQuery(sprintf($query, 'IP-Adresse', '192.0.2.1'));
        $this->assertXPathQuery(sprintf($query, 'Hostname', 'host.example.net'));
        $this->assertXPathQuery(sprintf($query, 'Datum', '24.02.2014 13:21:32'));
        $this->assertXpathQueryContentContains('//tr[6]/td[1]', 'Typ');
        $this->assertXpathQuery('//tr[6]/td[2]/select[@name="Type"]/option[not(@value)]');
        $this->assertXpathQueryContentContains('//tr[7]/td[1]', 'Beschreibung');
        $this->assertXpathQuery('//tr[7]/td[2]/input[@type="text"][@name="Description"]');
        $this->assertXpathQuery('//input[@type="hidden"][@name="_csrf"]');
        $this->assertXpathQuery('//input[@type="submit"]');
        $this->assertNotXpathQuery('//*[@class="error"]');
    }

    public function testEditActionPostInvalid()
    {
        $postData = array('Type' => 'type', 'Description' => 'description');
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
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
        $this->_deviceManager->method('getDevice')
                             ->with('00:00:5E:00:53:00')
                             ->willReturn($device);
        $this->_deviceManager->expects($this->once())
                             ->method('getTypes')
                             ->willReturn(array());
        $this->_deviceForm = $this->getMockBuilder('Console\Form\NetworkDevice')
                                  ->setMethods(array('isValid', 'prepare', 'setData'))
                                  ->getMock();
        $this->_deviceForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_deviceForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(false));
        $this->_deviceForm->expects($this->once())
                          ->method('prepare');
        $this->_deviceForm->setOption('DeviceManager', $this->_deviceManager);
        $this->_deviceForm->init();
        $this->_deviceForm->get('Description')->setMessages(array('message'));
        $this->dispatch('/console/network/edit/?macaddress=00:00:5E:00:53:00', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//*[@class="error"]//*[text()="message"]');
    }

    public function testEditActionPostValid()
    {
        $postData = array('Type' => 'type', 'Description' => 'description');
        $this->_deviceForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_deviceForm->expects($this->once())
                          ->method('getData')
                          ->will($this->returnValue($postData + array('_csrf' => '', 'Submit' => '')));
        $this->_deviceForm->expects($this->once())
                          ->method('isValid')
                          ->will($this->returnValue(true));
        $this->_deviceForm->expects($this->never())
                          ->method('prepare');
        $this->_deviceManager->expects($this->once())
                             ->method('saveDevice')
                             ->with('00:00:5E:00:53:00', 'type', 'description');
        $this->dispatch('/console/network/edit/?macaddress=00:00:5E:00:53:00', 'POST', $postData);
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testEditActionMissingParams()
    {
        $this->_deviceManager->expects($this->once())
                             ->method('getDevice')
                             ->with(null)
                             ->will($this->throwException(new \Model\Network\RuntimeException));
        $this->dispatch('/console/network/edit/');
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testDeleteActionGet()
    {
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
        $macAddress->expects($this->any())
                   ->method('__toString')
                   ->will($this->returnValue('00:00:5E:00:53:00'));
        $device = array(
            'MacAddress' => $macAddress,
            'Hostname' => 'host.example.net',
            'Description' => null,
        );
        $this->_deviceManager->expects($this->once())
                             ->method('getDevice')
                             ->with('00:00:5E:00:53:00')
                             ->willReturn($device);
        $this->_deviceManager->expects($this->never())->method('deleteDevice');
        $this->dispatch('/console/network/delete/?macaddress=00:00:5E:00:53:00');
        $this->assertResponseStatusCode(200);
        $this->assertContains('host.example.net', $this->getResponse()->getContent());
        $this->assertContains('00:00:5E:00:53:00', $this->getResponse()->getContent());
    }

    public function testDeleteActionPostNo()
    {
        $this->_deviceManager->expects($this->never())->method('getDevice');
        $this->_deviceManager->expects($this->never())->method('deleteDevice');
        $this->dispatch('/console/network/delete/?macaddress=00:00:5E:00:53:00', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testDeleteActionPostYes()
    {
        $this->_deviceManager->expects($this->never())->method('getDevice');
        $this->_deviceManager->expects($this->once())->method('deleteDevice')->with('00:00:5E:00:53:00');
        $this->dispatch('/console/network/delete/?macaddress=00:00:5E:00:53:00', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/network/index/');
    }

    public function testDeleteActionMissingParams()
    {
        $this->_deviceManager->expects($this->once())
                             ->method('getDevice')
                             ->with(null)
                             ->will($this->throwException(new \Model\Network\RuntimeException));
        $this->_deviceManager->expects($this->never())->method('deleteDevice');
        $this->dispatch('/console/network/delete/');
        $this->assertRedirectTo('/console/network/index/');
    }
}
