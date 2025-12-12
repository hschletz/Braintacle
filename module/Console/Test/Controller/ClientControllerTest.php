<?php

/**
 * Tests for ClientController
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Console\Form\Import;
use Console\Form\ProductKey;
use Console\Test\AbstractControllerTestCase;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Text;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Library\Form\Element\Submit;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Client\WindowsInstallation;
use Model\Config;
use Model\Registry\RegistryManager;
use Model\SoftwareManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for ClientController
 */
class ClientControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockObject|ClientManager
     */
    protected $_clientManager;

    /**
     * @var MockObject|RegistryManager
     */
    protected $_registryManager;

    /**
     * @var MockObject|SoftwareManager
     */
    protected $_softwareManager;

    /**
     * @var MockObject|Config
     */
    protected $_config;

    /**
     * Sample client data
     * @var array[]
     */
    protected $_sampleClients = array(
        array(
            'Id' => 1,
            'Name' => 'name1',
            'UserName' => 'username1',
            'OsName' => 'osname1',
            'Type' => 'type1',
            'CpuClock' => 'cpuclock1',
            'PhysicalMemory' => 'physicalmemory1',
            'InventoryDate' => '2014-05-11 12:35',
            'Registry.value' => 'registry1',
            'CustomFields.customField' => '<custom1>',
            'CustomFields.TAG' => 'category1',
        ),
        array(
            'Id' => 2,
            'Name' => 'name2',
            'UserName' => 'username2',
            'OsName' => 'osname2',
            'Type' => 'type2',
            'CpuClock' => 'cpuclock2',
            'PhysicalMemory' => 'physicalmemory2',
            'InventoryDate' => '2014-05-12 11:14',
            'Registry.value' => 'registry2',
            'CustomFields.customField' => '<custom2>',
            'CustomFields.TAG' => 'category2',
        )
    );

    /**
     * Default column set
     * @var string[]
     */
    protected $_defaultColumns = array(
        'Name',
        'UserName',
        'OsName',
        'Type',
        'CpuClock',
        'PhysicalMemory',
        'InventoryDate'
    );

    public function setUp(): void
    {
        parent::setUp();

        $this->_clientManager = $this->createMock('Model\Client\ClientManager');
        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_softwareManager = $this->createMock('Model\SoftwareManager');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Client\ClientManager', $this->_clientManager);
        $serviceManager->setService('Model\Registry\RegistryManager', $this->_registryManager);
        $serviceManager->setService('Model\SoftwareManager', $this->_softwareManager);

        $flashMessages = $this->createStub(FlashMessages::class);
        $flashMessages->method('get')->willReturn([]);
        $serviceManager->setService(FlashMessages::class, $flashMessages);

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getPathForRoute')->willReturnCallback(
            fn($name, $routeArguments) => "{$name}/{$routeArguments['id']}"
        );
        $serviceManager->setService(RouteHelper::class, $routeHelper);

        $formManager = $serviceManager->get('FormElementManager');
        $formManager->setService('Console\Form\CustomFields', $this->createMock('Console\Form\CustomFields'));
        $formManager->setService('Console\Form\Import', $this->createMock(Import::class));
        $formManager->setService('Console\Form\ProductKey', $this->createMock('Console\Form\ProductKey'));
    }

    public function testInvalidClient()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClient')
            ->with(42)
            ->will($this->throwException(new \RuntimeException()));
        $this->dispatch('/console/client/general/?id=42');
        $this->assertRedirectTo('/console/client/index/');
        $this->assertContains(
            'Der angeforderte Client existiert nicht.',
            $this->getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testIndexActionWithoutParams()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClients')
            ->with(
                $this->_defaultColumns,
                'InventoryDate',
                'desc',
                null,
                null,
                null,
                null,
                true,
                false
            )
            ->willReturn($this->_sampleClients);
        $this->dispatch('/console/client/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//p[@class="textcenter"]', "\nAnzahl Clients: 2\n");
        $this->assertXpathQueryCount('//th', 7);
        $this->assertXpathQueryCount('//th[@class="textright"]', 2); // CpuClock and PhysicalMemory
        $this->assertXpathQueryContentContains(
            '//td/a[@href="showClientGeneral/2"]',
            'name2'
        );
    }

    public function testIndexActionWithColumnList()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClients')
            ->with(
                array('Name', 'InventoryDate'),
                'InventoryDate',
                'desc',
                null,
                null,
                null,
                null,
                true,
                false
            )
            ->willReturn($this->_sampleClients);
        $this->dispatch('/console/client/index/?columns=Name,InventoryDate');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//th', 2);
    }

    public function testIndexActionWithMangledOsNames()
    {
        $sampleClients = array(
            array('OsName' => 'Microsoft OS version1'),
            array('OsName' => "Microsoft\xC2\xAE OS version2"),
            array('OsName' => 'not Microsoft OS'),
        );
        $this->_clientManager->method('getClients')
            ->with(
                array('OsName'),
                'InventoryDate',
                'desc',
                null,
                null,
                null,
                null,
                true,
                false
            )
            ->willReturn($sampleClients);
        $this->dispatch('/console/client/index/?columns=OsName');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]', "\nOS version1\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[1]', "\nOS version2\n");
        $this->assertXpathQueryContentContains('//tr[4]/td[1]', "\nnot Microsoft OS\n");
    }

    public function testIndexActionWithValidJumpto()
    {
        $this->_clientManager->expects($this->once())->method('getClients')->willReturn($this->_sampleClients);

        $this->dispatch('/console/client/index/?jumpto=software');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="showClientGeneral/2"]',
            'name2'
        );
    }

    public function testIndexActionWithInvalidJumpto()
    {
        $this->_clientManager->expects($this->once())->method('getClients')->willReturn($this->_sampleClients);

        $this->dispatch('/console/client/index/?jumpto=invalid');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="showClientGeneral/2"]',
            'name2'
        );
    }

    public function testIndexActionWithBuiltinSingleFilter()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClients')
            ->with(
                $this->_defaultColumns,
                'InventoryDate',
                'desc',
                'PackageError',
                'packageName',
                null,
                null,
                true,
                false
            )
            ->willReturn($this->_sampleClients);
        $this->dispatch('/console/client/index/?filter=PackageError&search=packageName');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Clients, bei denen die Installation von Paket 'packageName' fehlgeschlagen ist\n"
        );
    }

    public function testIndexActionWithBuiltinMultiFilter()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClients')
            ->with(
                $this->_defaultColumns,
                'InventoryDate',
                'desc',
                ['NetworkInterface.Subnet', 'NetworkInterface.Netmask'],
                ['192.0.2.0', '255.255.255.0'],
                [null, null],
                [null, null],
                true,
                false
            )
            ->willReturn($this->_sampleClients);
        $this->dispatch(
            '/console/client/index/?' .
                'filter1=NetworkInterface.Subnet&search1=192.0.2.0&' .
                'filter2=NetworkInterface.Netmask&search2=255.255.255.0'
        );
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Clients mit Interface in Netzwerk 192.0.2.0/24\n"
        );
    }

    public function testIndexActionWithBuiltinDistinctFilter()
    {
        $this->_clientManager->expects($this->once())
            ->method('getClients')
            ->with(
                $this->_defaultColumns,
                'InventoryDate',
                'desc',
                'Software',
                'name',
                null,
                null,
                true,
                true
            )
            ->willReturn($this->_sampleClients);
        $this->dispatch('/console/client/index/?filter=Software&search=name&distinct=');
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionMessages()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with(FlashMessages::Success)->willReturn(['successMessage']);

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService(FlashMessages::class, $flashMessages);

        $this->_clientManager->expects($this->once())->method('getClients')->willReturn(array());

        $this->dispatch('/console/client/index/');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="successMessage"]');
    }

    public function testWindowsActionGet()
    {
        $key = new Text('Key');
        $key->setLabel('Product key (if different)');

        $csrf = new Csrf('_csrf');
        $submit = new Submit('Submit');

        $form = $this->createPartialMock(ProductKey::class, ['setData', 'isValid', 'prepare', 'get']);
        $form->expects($this->once())
            ->method('setData')
            ->with(array('Key' => 'manual_product_key'));
        $form->expects($this->never())
            ->method('isValid');
        $form->expects($this->once())
            ->method('prepare');
        $form->method('get')->willReturnMap([
            ['Key', $key],
            ['_csrf', $csrf],
            ['Submit', $submit],
        ]);

        $formManager = $this->getApplicationServiceLocator()->get('FormElementManager');
        $formManager->setAllowOverride(true);
        $formManager->setService('Console\Form\ProductKey', $form);

        $windows = array(
            'Company' => 'company',
            'Owner' => 'owner',
            'ProductId' => 'product_id',
            'ProductKey' => 'product_key',
            'ManualProductKey' => 'manual_product_key',
        );
        $this->_softwareManager->expects($this->never())->method('setProductKey');

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = $windows;

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/windows/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form[not(@action)][@method="POST"]');
        $query = '//td[@class="label"][text()="%s"]/following::td[1][text()="%s"]';
        $this->assertXPathQuery(sprintf($query, 'Firma', 'company'));
        $this->assertXPathQuery(sprintf($query, 'Besitzer', 'owner'));
        $this->assertXPathQuery(sprintf($query, 'Produkt-ID', 'product_id'));
        $this->assertXPathQuery(sprintf($query, 'Lizenzschlüssel (vom Agenten ermittelt)', 'product_key'));
        $this->assertXpathQueryContentContains('//tr[5]/td[1]', 'Lizenzschlüssel (falls verschieden)');
        $this->assertXpathQuery('//tr[5]/td[2]/input[@type="text"][@name="Key"]');
        $this->assertXpathQuery('//input[@type="hidden"][@name="_csrf"]');
        $this->assertXpathQuery('//input[@type="submit"]');
        $this->assertNotXpathQuery('//*[@class="error"]');
    }

    public function testWindowsActionPostInvalid()
    {
        $postData = ['key' => 'entered_key'];

        $key = new Text('Key');
        $key->setLabel('Product key (if different)');
        $key->setMessages(['message']);

        $csrf = new Csrf('_csrf');
        $submit = new Submit('Submit');

        $form = $this->createPartialMock(ProductKey::class, ['setData', 'isValid', 'prepare', 'get']);
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $form->expects($this->once())
            ->method('prepare');
        $form->method('get')->willReturnMap([
            ['Key', $key],
            ['_csrf', $csrf],
            ['Submit', $submit],
        ]);

        $formManager = $this->getApplicationServiceLocator()->get('FormElementManager');
        $formManager->setAllowOverride(true);
        $formManager->setService('Console\Form\ProductKey', $form);

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = [
            'Company' => 'company',
            'Owner' => 'owner',
            'ProductId' => 'product_id',
            'ProductKey' => 'product_key',
        ];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->_softwareManager->expects($this->never())->method('setProductKey');
        $this->dispatch('/console/client/windows/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//*[@class="error"]//*[text()="message"]');
    }

    public function testWindowsActionPostValid()
    {
        $postData = array('Key' => 'entered_key');
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\ProductKey');
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($postData));

        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->_softwareManager->expects($this->once())
            ->method('setProductKey')
            ->with($client, 'entered_key');

        $this->dispatch('/console/client/windows/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/windows/?id=1');
    }

    public function testNetworkActionSettingsOnlyUnix()
    {
        // DnsServer and DefaultGateway typically show up both or not at all, so
        // they are not tested separately.
        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client->dnsDomain = 'dns_domain';
        $client->dnsServer = 'dns_server';
        $client->defaultGateway = 'default_gateway';
        $client['Windows'] = null;
        $client['NetworkInterface'] = [];
        $client['Modem'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $this->assertXpathQueryCount('//tr', 3);
        $query = "//td[text()='\n%s\n']/following::td[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'Hostname', 'name.dns_domain'));
        $this->assertXPathQuery(sprintf($query, 'DNS-Server', 'dns_server'));
        $this->assertXPathQuery(sprintf($query, 'Standardgateway', 'default_gateway'));
        $this->assertNotXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertNotXpathQuery("//h2[text()='\nModems\n']");
    }

    public function testNetworkActionSettingsOnlyWindows()
    {
        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client->dnsDomain = null;
        $client->dnsServer = null;
        $client->defaultGateway = null;
        $client['Windows'] = ['Workgroup' => 'workgroup'];
        $client['NetworkInterface'] = [];
        $client['Modem'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $this->assertXpathQueryCount('//tr', 1);
        $this->assertXpathQuery("//td[text()='\nArbeitsgruppe\n']/following::td[1][text()='\nworkgroup\n']");
        $this->assertNotXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertNotXpathQuery("//h2[text()='\nModems\n']");
    }

    public function testNetworkActionInterfacesOnly()
    {
        $macAddress = $this->createMock('Library\MacAddress');
        $macAddress->expects($this->exactly(2))
            ->method('getAddress')
            ->will($this->onConsecutiveCalls('mac_address_regular', 'mac_address_blacklisted'));
        $macAddress->expects($this->exactly(2))
            ->method('getVendor')
            ->will($this->onConsecutiveCalls('vendor1', null));
        $interface = array(
            'Description' => 'description',
            'Rate' => 'data_rate',
            'MacAddress' => $macAddress,
            'IpAddress' => 'ip_address',
            'Netmask' => 'netmask',
            'Gateway' => 'gateway',
            'DhcpServer' => 'dhcp_server',
            'Status' => 'status',
        );
        $interfaces = array(
            $interface + array('IsBlacklisted' => false),
            $interface + array('IsBlacklisted' => true),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client->dnsDomain = null;
        $client->dnsServer = null;
        $client->defaultGateway = null;
        $client['Windows'] = null;
        $client['NetworkInterface'] = $interfaces;
        $client['Modem'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $this->assertXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertXpathQuery("//td[text()='\nmac_address_regular (vendor1)\n'][not(@class)]");
        $this->assertXpathQuery("//td/span[text()='mac_address_blacklisted'][@class='blacklisted']");
        $this->assertNotXpathQuery("//h2[text()='\nModems\n']");
    }

    public function testNetworkActionModemsOnly()
    {
        $modem = array(
            'Type' => 'type',
            'Name' => 'name',
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client->dnsDomain = null;
        $client->dnsServer = null;
        $client->defaultGateway = null;
        $client['Windows'] = null;
        $client['NetworkInterface'] = [];
        $client['Modem'] = [$modem];

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);

        $this->assertNotXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $this->assertNotXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertXpathQuery("//h2[text()='\nModems\n']");
        $this->assertXpathQueryCount('//td', 2);
    }

    public function testSystemActionUnixNoSlots()
    {
        $controllers = array(
            array(
                'Type' => 'type',
                'Name' => 'name',
            ),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = null;
        $client['MemorySlot'] = [];
        $client['Controller'] = $controllers;
        $client['ExtensionSlot'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nRAM-Steckplätze\n']");
        $this->assertNotXpathQuery("//h2[text()='\nErweiterungssteckplätze\n']");
        $this->assertXpathQuery("//h2[text()='\nController\n']");
        $this->assertXpathQueryCount('//tr[1]/th', 2);
        $this->assertXpathQueryContentContains('//tr[1]/th[1]', "\nName\n");
        $this->assertXpathQueryContentContains('//tr[1]/th[2]', "\nTyp\n");
    }

    public function testSystemActionWindows()
    {
        $controllers = array(
            array(
                'Type' => 'type',
                'Manufacturer' => 'manufacturer',
                'Name' => 'name',
            ),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = new WindowsInstallation();
        $client['MemorySlot'] = [];
        $client['Controller'] = $controllers;
        $client['ExtensionSlot'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//tr[1]/th', 3);
        $this->assertXpathQueryContentContains('//tr[1]/th[1]', "\nHersteller\n");
        $this->assertXpathQueryContentContains('//tr[1]/th[2]', "\nName\n");
        $this->assertXpathQueryContentContains('//tr[1]/th[3]', "\nTyp\n");
    }

    public function testSystemActionMemorySlots()
    {
        $slots = array(
            array(
                'SlotNumber' => 1,
                'Size' => '1024',
                'Type' => 'type1',
                'Clock' => '333',
                'Serial' => 'serial1',
                'Caption' => 'caption1',
                'Description' => 'description1',
            ),
            array(
                'SlotNumber' => 1,
                'Size' => null,
                'Type' => 'type1',
                'Clock' => null,
                'Serial' => 'serial1',
                'Caption' => 'caption1',
                'Description' => 'description1',
            ),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = null;
        $client['MemorySlot'] = $slots;
        $client['Controller'] = [];
        $client['ExtensionSlot'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nRAM-Steckplätze\n']");
        $this->assertXPathQuery("//tr[2]/td[2][text()='\n1024\xC2\xA0MB\n']");
        $this->assertXPathQuery("//tr[2]/td[4][text()='\n333\xC2\xA0MHz\n']");
        $this->assertXPathQuery("//tr[3]/td[2][text()='\n\n']");
        $this->assertXPathQuery("//tr[3]/td[4][text()='\n\n']");
    }

    public function testSystemActionExtensionSlots()
    {
        $slots = array(
            array(
                'Name' => 'name',
                'Description' => 'description',
                'Status' => 'status',
            ),
            array(
                'Name' => '<name>',
                'Description' => 'description',
                'Status' => 'status',
                'SlotId' => null,
            ),
            array(
                'Name' => '<name>',
                'Description' => 'description',
                'Status' => 'status',
                'SlotId' => '<id>'
            ),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = null;
        $client['MemorySlot'] = [];
        $client['Controller'] = [];
        $client['ExtensionSlot'] = $slots;

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nErweiterungssteckplätze\n']");
        $this->assertXpathQueryCount('//tr', 4);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]', "\nname\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[1]', "\n<name>\n");
        $this->assertXpathQueryContentContains('//tr[4]/td[1]', "\n<name> (#<id>)\n");
    }

    public function testPrintersAction()
    {
        $printers = array(
            array(
                'Name' => 'name',
                'Driver' => 'driver',
                'Port' => 'port',
                'Description' => 'description',
            ),
        );
        $client = new Client();
        $client->id = 1;
        $client->name = 'test';
        $client['Windows'] = null;
        $client['Printer'] = $printers;

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/printers/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//tr', 2);
    }

    public function testMsofficeActionInstalled()
    {
        $products = array(
            array(
                'Name' => 'name1',
                'Architecture' => 32,
                'ProductKey' => 'key1',
                'ProductId' => 'id1',
                'ExtraDescription' => null,
                'Guid' => null,
            ),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->exactly(2))
            ->method('getItems')
            ->will($this->onConsecutiveCalls($products, array()));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nInstallierte Microsoft Office-Produkte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nUngenutzte Microsoft Office-Lizenzen\n']");
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\n32 Bit\n");
    }

    public function testMsofficeActionUnused()
    {
        $products = array(
            array(
                'Name' => 'name1',
                'Architecture' => 32,
                'ProductKey' => 'key1',
                'ProductId' => 'id1',
                'ExtraDescription' => null,
                'Guid' => null,
            ),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->exactly(2))
            ->method('getItems')
            ->will($this->onConsecutiveCalls(array(), $products));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nInstallierte Microsoft Office-Produkte\n']");
        $this->assertXpathQuery("//h2[text()='\nUngenutzte Microsoft Office-Lizenzen\n']");
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\n32 Bit\n");
    }

    public function testMsofficeActionExtraDescription()
    {
        $products = array(
            array(
                'Name' => 'name1',
                'Architecture' => 32,
                'ProductKey' => 'key1',
                'ProductId' => 'id1',
                'ExtraDescription' => null,
                'Guid' => null,
            ),
            array(
                'Name' => 'name2',
                'Architecture' => 32,
                'ProductKey' => 'key2',
                'ProductId' => 'id2',
                'ExtraDescription' => 'description',
                'Guid' => null,
            ),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->exactly(2))
            ->method('getItems')
            ->will($this->onConsecutiveCalls($products, array()));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]', "\nname1\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[1]', "\nname2 (description)\n");
    }

    public function testMsofficeActionGuid()
    {
        $products = array(
            array(
                'Name' => 'name1',
                'Architecture' => 32,
                'ProductKey' => 'key1',
                'ProductId' => 'id1',
                'ExtraDescription' => null,
                'Guid' => null,
            ),
            array(
                'Name' => 'name2',
                'Architecture' => 32,
                'ProductKey' => 'key2',
                'ProductId' => 'id2',
                'ExtraDescription' => null,
                'Guid' => 'guid',
            ),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->exactly(2))
            ->method('getItems')
            ->will($this->onConsecutiveCalls($products, array()));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//tr[2]/td[1]/span');
        $this->assertXpathQueryContentContains('//tr[3]/td[1]/span[@title="GUID: guid"]', 'name2');
    }

    public function testRegistryActionNoValues()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->once())
            ->method('getItems')
            ->with('RegistryData', 'Value', 'asc')
            ->willReturn(array());
        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_registryManager->expects($this->once())->method('getValueDefinitions')->willReturn(array());
        $this->dispatch('/console/client/registry/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery('//p/a[@href="/console/preferences/registryvalues/"]');
    }

    public function testRegistryActionWithValues()
    {
        $data = array(
            'Value' => '<value>',
            'Data' => 'data',
        );
        $values = array(
            array(
                'Name' => 'unused',
                'FullPath' => null,
            ),
            array(
                'Name' => '<value>',
                'FullPath' => 'full_path',
            )
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->once())
            ->method('getItems')
            ->with('RegistryData', 'Value', 'asc')
            ->willReturn(array($data));
        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_registryManager->expects($this->once())->method('getValueDefinitions')->willReturn($values);
        $this->dispatch('/console/client/registry/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]/span[@title="full_path"]', "\n<value>\n");
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\ndata\n");
        $this->assertXpathQuery('//p/a[@href="/console/preferences/registryvalues/"]');
    }

    public function testVirtualmachinesActionNoMachines()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->once())
            ->method('getItems')
            ->with('VirtualMachine', 'Name', 'asc')
            ->willReturn(array());
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/virtualmachines/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
    }

    public function testVirtualmachinesActionWithMachines()
    {
        $vms = array(
            array(
                'Name' => 'name1',
                'Status' => 'status1',
                'Product' => 'product1',
                'Type' => 'type1',
                'Uuid' => 'uuid1',
                'GuestMemory' => '1024',
            ),
            array(
                'Name' => 'name2',
                'Status' => 'status2',
                'Product' => 'product2',
                'Type' => 'type2',
                'Uuid' => 'uuid2',
                'GuestMemory' => '',
            ),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->id = 1;
        $client->name = 'test';
        $client->expects($this->once())
            ->method('getItems')
            ->with('VirtualMachine', 'Name', 'asc')
            ->willReturn($vms);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/virtualmachines/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//tr[2]/td[6]', "\n1024 MB\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[6]', "\n\n");
    }

    public function testMiscActionAudiodevices()
    {
        $audiodevice = array(
            'Manufacturer' => 'manufacturer',
            'Name' => 'name',
            'Description' => 'description',
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $client['AudioDevice'] = [$audiodevice];
        $client['InputDevice'] = [];
        $client['Port'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nAudiogeräte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nEingabegeräte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nAnschlüsse\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryCount('//tr', 2);
    }

    public function testMiscActionInputdevices()
    {
        $inputdevices = array(
            array(
                'Type' => 'type',
                'Manufacturer' => 'manufacturer1',
                'Description' => 'description1',
                'Comment' => 'comment1',
                'Interface' => 'interface1',
            ),
            array(
                'Type' => 'Keyboard',
                'Manufacturer' => 'manufacturer1',
                'Description' => 'description1',
                'Comment' => 'comment1',
                'Interface' => 'interface1',
            ),
            array(
                'Type' => 'Pointing',
                'Manufacturer' => 'manufacturer1',
                'Description' => 'description1',
                'Comment' => 'comment1',
                'Interface' => 'interface1',
            ),
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $client['AudioDevice'] = [];
        $client['InputDevice'] = $inputdevices;
        $client['Port'] = [];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudiogeräte\n']");
        $this->assertXpathQuery("//h2[text()='\nEingabegeräte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nAnschlüsse\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]', "\ntype\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[1]', "\nTastatur\n");
        $this->assertXpathQueryContentContains('//tr[4]/td[1]', "\nZeigegerät\n");
    }

    public function testMiscActionPortsWindows()
    {
        $port = array(
            'Type' => 'manufacturer',
            'Name' => 'name',
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = new WindowsInstallation();
        $client['AudioDevice'] = [];
        $client['InputDevice'] = [];
        $client['Port'] = [$port];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudiogeräte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nEingabegeräte\n']");
        $this->assertXpathQuery("//h2[text()='\nAnschlüsse\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryCount('//tr', 2);
        $this->assertNotXpathQueryContentContains('//th', "\nVerbinder\n");
    }

    public function testMiscActionPortsUnix()
    {
        $port = array(
            'Type' => 'manufacturer',
            'Name' => 'name',
            'Connector' => 'connector',
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $client['AudioDevice'] = [];
        $client['InputDevice'] = [];
        $client['Port'] = [$port];

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudiogeräte\n']");
        $this->assertNotXpathQuery("//h2[text()='\nEingabegeräte\n']");
        $this->assertXpathQuery("//h2[text()='\nAnschlüsse\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryCount('//tr', 2);
        $this->assertXpathQueryContentContains('//th', "\nVerbinder\n");
    }

    public function testCustomfieldsActionFlashMessage()
    {
        $customFields = $this->createMock('Model\Client\CustomFields');
        $customFields->expects($this->once())
            ->method('getArrayCopy')
            ->will($this->returnValue(array()));

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $client['CustomFields'] = $customFields;

        $this->_clientManager->method('getClient')->willReturn($client);

        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->method('__invoke')->with(null)->willReturnSelf();
        $flashMessenger->expects($this->once())
            ->method('render')
            ->with('success')
            ->willReturn('<ul class="success"><li>successMessage</li></ul>');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('flashMessenger', $flashMessenger);

        $this->disableTranslator();
        $this->dispatch('/console/client/customfields/?id=1');
        $this->assertXpathQueryContentContains(
            '//ul[@class="success"]/li',
            'successMessage'
        );
    }

    public function testCustomfieldsActionGet()
    {
        $data = array('field1' => 'value1', 'field2' => 'value2');
        $customFields = $this->createMock('Model\Client\CustomFields');
        $customFields->expects($this->once())
            ->method('getArrayCopy')
            ->will($this->returnValue($data));

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $client['CustomFields'] = $customFields;

        $this->_clientManager->method('getClient')->willReturn($client);
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\CustomFields');
        $form->expects($this->never())
            ->method('isValid');
        $form->expects($this->never())
            ->method('getData');
        $form->expects($this->once())
            ->method('setData')
            ->with(array('Fields' => $data));
        $form->expects($this->once())
            ->method('render')
            ->will($this->returnValue('<form></form>'));
        $this->dispatch('/console/client/customfields/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertEmpty($this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
        $this->assertXpathQueryContentContains(
            '//p/a[@href="/console/preferences/customfields/"]',
            'Felder definieren'
        );
        $this->assertXpathQuery('//form');
    }

    public function testCustomfieldsActionPostInvalid()
    {
        $postData = array(
            '_csrf' => 'csrf',
            'Fields' => array('field1' => 'value1', 'field2' => 'value2')
        );

        $client = new Client();
        $client->id = 1;
        $client->name = 'name';
        $client['Windows'] = null;
        $this->_clientManager->method('getClient')->willReturn($client);

        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\CustomFields');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $form->expects($this->never())
            ->method('getData');
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->once())
            ->method('render');
        $this->dispatch('/console/client/customfields/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertEmpty($this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
        $this->assertXpathQueryContentContains(
            '//p/a[@href="/console/preferences/customfields/"]',
            'Felder definieren'
        );
    }

    public function testCustomfieldsActionPostValid()
    {
        $postData = array(
            '_csrf' => 'csrf',
            'Fields' => array('field1' => 'value1', 'field2' => 'value2')
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\CustomFields');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($postData));
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->never())
            ->method('render');
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->once())->method('setCustomFields')->with($postData['Fields']);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/customfields/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/customfields/?id=1');
        $this->assertContains(
            'Die Informationen wurden aktualisiert.',
            $this->getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testImportActionGet()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Import');
        $form->expects($this->never())
            ->method('isValid');
        $form->expects($this->never())
            ->method('setData');
        $form->expects($this->never())
            ->method('getData');
        $form->expects($this->once())
            ->method('render')
            ->will($this->returnValue('<form></form>'));

        $this->_clientManager->expects($this->never())->method('importFile');

        $this->dispatch('/console/client/import/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="error"]');
        $this->assertXpathQueryContentContains('//h1', "\nImport lokal erzeugter Inventardaten\n");
        $this->assertXPathQuery('//form');
    }

    public function testImportActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Import');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));
        $form->expects($this->once())
            ->method('setData')
            ->with($postData);
        $form->expects($this->never())
            ->method('getData');
        $form->expects($this->once())
            ->method('render');

        $this->_clientManager->expects($this->never())->method('importFile');

        $this->dispatch('/console/client/import/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="error"]');
        $this->assertXpathQueryContentContains('//h1', "\nImport lokal erzeugter Inventardaten\n");
    }

    public function testImportActionPostValidError()
    {
        $fileSpec = array('tmp_name' => 'uploaded_file');
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $postData = array('key' => 'value');
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Import');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('setData')
            ->with(
                array(
                    'File' => $fileSpec,
                    'key' => 'value',
                )
            );
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array('File' => $fileSpec)));
        $form->expects($this->once())
            ->method('render');

        $this->_clientManager->expects($this->once())
            ->method('importFile')
            ->with('uploaded_file')
            ->willThrowException(new \RuntimeException('<error message>'));

        $this->dispatch('/console/client/import/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="error"]',
            "\n<error message>\n"
        );
        $this->assertXpathQueryContentContains('//h1', "\nImport lokal erzeugter Inventardaten\n");
    }

    public function testImportActionPostValidSuccess()
    {
        $fileSpec = array('tmp_name' => 'uploaded_file');
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $postData = array('key' => 'value');
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Import');
        $form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));
        $form->expects($this->once())
            ->method('setData')
            ->with(
                array(
                    'File' => $fileSpec,
                    'key' => 'value',
                )
            );
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue(array('File' => $fileSpec)));
        $form->expects($this->never())
            ->method('render');

        $this->_clientManager->expects($this->once())
            ->method('importFile')
            ->with('uploaded_file');

        $this->dispatch('/console/client/import/', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
    }
}
