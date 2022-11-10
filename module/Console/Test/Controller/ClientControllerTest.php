<?php

/**
 * Tests for ClientController
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

use Console\Form\Import;
use Console\Form\Package\AssignPackagesForm;
use Console\Form\ProductKey;
use Console\Form\Search as SearchForm;
use Console\Mvc\Controller\Plugin\PrintForm;
use Console\View\Helper\Form\ClientConfig;
use Console\View\Helper\Form\Search as SearchHelper;
use DateTime;
use EmptyIterator;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Element\Text;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Laminas\I18n\View\Helper\DateFormat;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Laminas\View\Model\ViewModel;
use Library\Form\Element\Submit;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\ClientManager;
use Model\Client\Item\Software;
use Model\Client\WindowsInstallation;
use Model\Config;
use Model\Group\GroupManager;
use Model\Package\Assignment;
use Model\Registry\RegistryManager;
use Model\SoftwareManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for ClientController
 */
class ClientControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * @var MockObject|ClientManager
     */
    protected $_clientManager;

    /**
     * @var MockObject|GroupManager
     */
    protected $_groupManager;

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
        $this->_groupManager = $this->createMock('Model\Group\GroupManager');
        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_softwareManager = $this->createMock('Model\SoftwareManager');
        $this->_config = $this->createMock('Model\Config');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Client\ClientManager', $this->_clientManager);
        $serviceManager->setService('Model\Group\GroupManager', $this->_groupManager);
        $serviceManager->setService('Model\Registry\RegistryManager', $this->_registryManager);
        $serviceManager->setService('Model\SoftwareManager', $this->_softwareManager);
        $serviceManager->setService('Model\Config', $this->_config);

        $formManager = $serviceManager->get('FormElementManager');
        $formManager->setService('Console\Form\ClientConfig', $this->createMock('Console\Form\ClientConfig'));
        $formManager->setService('Console\Form\CustomFields', $this->createMock('Console\Form\CustomFields'));
        $formManager->setService('Console\Form\DeleteClient', $this->createMock('Console\Form\DeleteClient'));
        $formManager->setService('Console\Form\GroupMemberships', $this->createMock('Console\Form\GroupMemberships'));
        $formManager->setService('Console\Form\Import', $this->createMock(Import::class));
        $formManager->setService('Console\Form\ProductKey', $this->createMock('Console\Form\ProductKey'));
        $formManager->setService('Console\Form\Search', $this->createMock('Console\Form\Search'));
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

    public function testMenuForWindowsClients()
    {
        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => $this->createMock('Model\Client\WindowsInstallation'),
            'Printer' => array(),
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertXpathQuery($query . '/a[@href="/console/client/windows/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/client/msoffice/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/client/registry/?id=1"]');
    }

    public function testMenuForNonWindowsClients()
    {
        $client = array(
            'Name' => 'name',
            'Windows' => null,
            'Printer' => array(),
        );
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/windows/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/msoffice/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/registry/?id=1"]');
    }

    public function testIndexActionWithoutParams()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
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
            '//td/a[@href="/console/client/general/?id=2"]',
            'name2'
        );
    }

    public function testIndexActionWithColumnList()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
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
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_clientManager->expects($this->once())->method('getClients')->willReturn($this->_sampleClients);

        $this->dispatch('/console/client/index/?jumpto=software');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/client/software/?id=2"]',
            'name2'
        );
    }

    public function testIndexActionWithInvalidJumpto()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_clientManager->expects($this->once())->method('getClients')->willReturn($this->_sampleClients);

        $this->dispatch('/console/client/index/?jumpto=invalid');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/client/general/?id=2"]',
            'name2'
        );
    }

    public function testIndexActionWithBuiltinSingleFilter()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
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
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
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

    public function testIndexActionWithBuiltinSoftwareFilter()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 $this->_defaultColumns,
                                 'InventoryDate',
                                 'desc',
                                 'Software',
                                 "\xc2\x99", // Incorrect representation of TM symbol
                                 null,
                                 null,
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $this->dispatch('/console/client/index/?filter=Software&search=%C2%99');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Clients, auf denen die Software '\xe2\x84\xa2' installiert ist\n"
        ); // Corrected representation of TM symbol
    }

    public function testIndexActionWithBuiltinDistinctFilter()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
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

    public function testIndexActionWithCustomEqualitySearchOnDefaultColumn()
    {
        $formData = array(
            'filter' => 'Name',
            'search' => 'test',
            'operator' => 'eq',
            'invert' => '1',
            'customSearch' => 'button',
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate'),
                                 'InventoryDate',
                                 'desc',
                                 'Name',
                                 'test',
                                 'eq',
                                 '1',
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $query = 'filter=Name&search=test&operator=eq&invert=1';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            '//p[@class="textcenter"][contains(text(), "2 Treffer")]'
        );
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/client/search/?$query'][text()='Filter bearbeiten']"
        );
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/group/add/?$query'][text()='In Gruppe speichern']"
        );
    }

    public function testIndexActionWithCustomEqualitySearchOnDateColumn()
    {
        $date = new \DateTime('2014-05-12');
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with(
                 array(
                    'filter' => 'InventoryDate',
                    'search' => '2014-05-12',
                    'operator' => 'eq',
                    'invert' => '1',
                    'customSearch' => 'button',
                 )
             );
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will(
                 $this->returnValue(
                     array(
                        'filter' => 'InventoryDate',
                        'search' => $date,
                        'operator' => 'eq',
                        'invert' => '1',
                        'customSearch' => 'button',
                     )
                 )
             );
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate'),
                                 'InventoryDate',
                                 'desc',
                                 'InventoryDate',
                                 $date,
                                 'eq',
                                 '1',
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $query = 'filter=InventoryDate&search=2014-05-12&operator=eq&invert=1';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/group/add/?$query'][text()='In Gruppe speichern']"
        );
    }

    public function testIndexActionWithCustomEqualitySearchOnNonDefaultColumn()
    {
        // Equality search should not add the searched column.
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will(
                 $this->returnValue(
                     array(
                        'filter' => 'CpuType',
                        'search' => 'value',
                        'operator' => 'eq',
                        'invert' => '0',
                        'customSearch' => 'button',
                     )
                 )
             );
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate'),
                                 'InventoryDate',
                                 'desc',
                                 'CpuType',
                                 'value',
                                 'eq',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn(array());
        $query = 'filter=CpuType&search=value&operator=eq&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionWithCustomNonEqualitySearchOnNonDefaultColumn()
    {
        // Non-equality search should add the searched column.
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will(
                 $this->returnValue(
                     array(
                        'filter' => 'CpuType',
                        'search' => 'value',
                        'operator' => 'ne',
                        'invert' => '0',
                        'customSearch' => 'button',
                     )
                 )
             );
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'CpuType'),
                                 'InventoryDate',
                                 'desc',
                                 'CpuType',
                                 'value',
                                 'ne',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn(array());
        $query = 'filter=CpuType&search=value&operator=ne&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionWithCustomInvertedEqualitySearchOnNonDefaultColumn()
    {
        // Inverted equality search should add the searched column.
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will(
                 $this->returnValue(
                     array(
                        'filter' => 'CpuType',
                        'search' => 'value',
                        'operator' => 'eq',
                        'invert' => '1',
                        'customSearch' => 'button',
                     )
                 )
             );
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'CpuType'),
                                 'InventoryDate',
                                 'desc',
                                 'CpuType',
                                 'value',
                                 'eq',
                                 '1',
                                 true,
                                 false
                             )
                             ->willReturn(array());
        $query = 'filter=CpuType&search=value&operator=eq&invert=1';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionWithCustomSearchOnRegistry()
    {
        $formData = array(
            'filter' => 'Registry.value',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'Registry.value'),
                                 'InventoryDate',
                                 'desc',
                                 'Registry.value',
                                 'test',
                                 'like',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $query = 'filter=Registry.value&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "value");
    }

    public function testIndexActionWithCustomSearchOnCustomFieldText()
    {
        $formData = array(
            'filter' => 'CustomFields.customField',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'CustomFields.customField'),
                                 'InventoryDate',
                                 'desc',
                                 'CustomFields.customField',
                                 'test',
                                 'like',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $query = 'filter=CustomFields.customField&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "customField");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\n<custom1>\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\n<custom2>\n");
    }

    public function testIndexActionWithCustomSearchOnCustomFieldDate()
    {
        $formData = array(
            'filter' => 'CustomFields.customField',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));

        $sampleClients = $this->_sampleClients;
        $sampleClients[0]['CustomFields.customField'] = new \DateTime('2015-04-11 10:31:00');
        $sampleClients[1]['CustomFields.customField'] = new \DateTime('2015-04-12 10:32:00');
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'CustomFields.customField'),
                                 'InventoryDate',
                                 'desc',
                                 'CustomFields.customField',
                                 'test',
                                 'like',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn($sampleClients);
        $query = 'filter=CustomFields.customField&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "customField");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\n11.04.2015\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\n12.04.2015\n");
    }

    public function testIndexActionWithCustomSearchOnCategory()
    {
        $formData = array(
            'filter' => 'CustomFields.TAG',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_clientManager->expects($this->once())
                             ->method('getClients')
                             ->with(
                                 array('Name', 'UserName', 'InventoryDate', 'CustomFields.TAG'),
                                 'InventoryDate',
                                 'desc',
                                 'CustomFields.TAG',
                                 'test',
                                 'like',
                                 '0',
                                 true,
                                 false
                             )
                             ->willReturn($this->_sampleClients);
        $query = 'filter=CustomFields.TAG&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "Kategorie");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\ncategory1\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\ncategory2\n");
    }

    public function testIndexActionWithInvalidCustomSearch()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('getData');
        $this->_clientManager->expects($this->never())->method('getClients');
        $query = 'filter=CpuClock&search=invalid&operator=lt&invert=1';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertRedirectTo("/console/client/search/?customSearch=button&$query");
    }

    public function testIndexActionMessages()
    {
        $flashMessenger = $this->createMock(FlashMessenger::class);
        $flashMessenger->method('__invoke')->with(null)->willReturnSelf();
        $flashMessenger->method('__call')
                       ->withConsecutive(
                           array('getMessagesFromNamespace', array('error')),
                           array('getMessagesFromNamespace', array('success'))
                       )->willReturnOnConsecutiveCalls(
                           ['error'],
                           ['success']
                       );
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('flashMessenger', $flashMessenger);

        $this->_clientManager->expects($this->once())->method('getClients')->willReturn(array());
        $this->disableTranslator();
        $this->dispatch('/console/client/index/');
        $this->assertXpathQuery('//ul[@class="error"]/li[text()="error"]');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="success"]');
    }

    public function testGeneralActionDefault()
    {
        $inventoryDate = new \DateTime('2014-05-29 11:16:15');
        $lastContactDate = new \DateTime('2014-05-29 11:17:34');

        $dateFormat = $this->createMock(DateFormat::class);
        $dateFormat->expects($this->exactly(2))
                   ->method('__invoke')
                   ->withConsecutive(
                       array($inventoryDate, \IntlDateFormatter::FULL, \IntlDateFormatter::LONG),
                       array($lastContactDate, \IntlDateFormatter::FULL, \IntlDateFormatter::LONG)
                   )
                   ->willReturnOnConsecutiveCalls('inventory_date', 'last_contact_date');
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('dateFormat', $dateFormat);

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'IdString' => 'id_string',
            'InventoryDate' => $inventoryDate,
            'LastContactDate' => $lastContactDate,
            'UserAgent' => 'user_agent',
            'Manufacturer' => 'manufacturer',
            'ProductName' => 'product_name',
            'IsSerialBlacklisted' => false,
            'Serial' => 'serial',
            'IsAssetTagBlacklisted' => false,
            'AssetTag' => 'asset_tag',
            'Type' => 'type',
            'OsName' => 'os_name',
            'OsVersionString' => 'os_version_string',
            'OsVersionNumber' => 'os_version_number',
            'OsComment' => 'os_comment',
            'CpuType' => 'cpu_type',
            'CpuClock' => 1234,
            'CpuCores' => 2,
            'MemorySlot' => array(array('Size' => 2), array('Size' => 3)),
            'PhysicalMemory' => 1234,
            'SwapMemory' => 5678,
            'UserName' => 'user_name',
            'Windows' => null,
            'Uuid' => 'uuid',
        ]);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertResponseStatusCode(200);

        $query = "//table/tr/td[text()='\n%s\n']/following::td[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'ID', 1));
        $this->assertXPathQuery(sprintf($query, 'ID-String', 'id_string'));
        $this->assertXPathQuery(sprintf($query, 'Datum der Inventarinformationen', 'inventory_date'));
        $this->assertXPathQuery(sprintf($query, 'Letzter Kontakt', 'last_contact_date'));
        $this->assertXPathQuery(sprintf($query, 'User-Agent', 'user_agent'));
        $this->assertXPathQuery(sprintf($query, 'Modell', 'manufacturer product_name'));
        $this->assertXPathQuery(sprintf($query, 'Seriennummer', 'serial'));
        $this->assertXPathQuery(sprintf($query, 'Asset tag', 'asset_tag'));
        $this->assertXPathQuery(sprintf($query, 'Typ', 'type'));
        $this->assertXPathQuery(sprintf($query, 'Betriebssystem', 'os_name os_version_string (os_version_number)'));
        $this->assertXPathQuery(sprintf($query, 'Kommentar', 'os_comment'));
        $this->assertXPathQuery(sprintf($query, 'CPU Typ', 'cpu_type'));
        $this->assertXPathQuery(sprintf($query, 'CPU Takt', "1234\xC2\xA0MHz"));
        $this->assertXPathQuery(sprintf($query, 'Anzahl der CPU-Kerne', 2));
        $this->assertXPathQuery(sprintf($query, 'Vom Agenten entdeckter Arbeitsspeicher', "5\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'Arbeitsspeicher lt. Betriebssystem', "1234\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'Auslagerungsspeicher', "5678\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'Letzter angemeldeter Benutzer', 'user_name'));
        $this->assertXPathQuery(sprintf($query, 'UUID', 'uuid'));
        $this->assertXpathQuery("//table/tr/td[text()='\nserial\n'][not(@class)]");
        $this->assertXpathQuery("//table/tr/td[text()='\nasset_tag\n'][not(@class)]");
    }

    public function testGeneralActionSerialBlacklisted()
    {
        $map = array(
            array('IsSerialBlacklisted', true),
            array('Serial', 'serial'),
            array('IsAssetTagBlacklisted', false),
            array('AssetTag', 'asset_tag'),
            array('MemorySlot', array()),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQuery("//td[text()='\nserial\n'][@class='blacklisted']");
        $this->assertXpathQuery("//td[text()='\nasset_tag\n'][not(@class)]");
    }

    public function testGeneralActionAssetTagBlacklisted()
    {
        $map = array(
            array('IsSerialBlacklisted', false),
            array('Serial', 'serial'),
            array('IsAssetTagBlacklisted', true),
            array('AssetTag', 'asset_tag'),
            array('MemorySlot', array()),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQuery("//td[text()='\nserial\n'][not(@class)]");
        $this->assertXpathQuery("//td[text()='\nasset_tag\n'][@class='blacklisted']");
    }

    public function testGeneralActionWindowsUser()
    {
        $map = array(
            array('Windows', array('UserDomain' => 'user_domain')),
            array('UserName', 'user_name'),
            array('MemorySlot', array()),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQueryContentContains('//td', "\nuser_name @ user_domain\n");
    }

    public function testGeneralActionWindowsNoArch()
    {
        $map = array(
            array('OsName', 'os_name'),
            array('OsVersionString', 'os_version_string'),
            array('OsVersionNumber', 'os_version_number'),
            array('Windows', array('CpuArchitecture' => null, 'UserDomain' => 'domain')),
            array('MemorySlot', array()),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->willReturnMap($map);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQueryContentContains(
            '//td',
            "\nos_name os_version_string (os_version_number)\n"
        );
    }

    public function testGeneralActionWindowsWithArch()
    {
        $map = array(
            array('OsName', 'os_name'),
            array('OsVersionString', 'os_version_string'),
            array('OsVersionNumber', 'os_version_number'),
            array('Windows', array('CpuArchitecture' => 'cpu_architecture', 'UserDomain' => 'domain')),
            array('MemorySlot', array()),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->willReturnMap($map);
        $client->method('offsetExists')->with('Windows')->willReturn(true);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQueryContentContains(
            '//td',
            "\nos_name os_version_string (os_version_number) \xE2\x80\x93 cpu_architecture\n"
        );
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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => $windows,
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => [
                'Company' => 'company',
                'Owner' => 'owner',
                'ProductId' => 'product_id',
                'ProductKey' => 'product_key',
            ],
        ]);

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
        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'DnsDomain' => 'dns_domain',
            'DnsServer' => 'dns_server',
            'DefaultGateway' => 'default_gateway',
            'NetworkInterface' => array(),
            'Modem' => array(),
        ]);

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
        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => array('Workgroup' => 'workgroup'),
            'DnsDomain' => null,
            'DnsServer' => null,
            'DefaultGateway' => null,
            'NetworkInterface' => array(),
            'Modem' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'DnsDomain' => null,
            'DnsServer' => null,
            'DefaultGateway' => null,
            'NetworkInterface' => $interfaces,
            'Modem' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'DnsDomain' => null,
            'DnsServer' => null,
            'DefaultGateway' => null,
            'NetworkInterface' => array(),
            'Modem' => array($modem),
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);

        $this->assertNotXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $this->assertNotXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertXpathQuery("//h2[text()='\nModems\n']");
        $this->assertXpathQueryCount('//td', 2);
    }

    public function testStorageActionWindows()
    {
        $devices = array(
            array(
                'Type' => 'DVD Writer', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => 'Fixed hard disk media', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => 'Fixedxhard disk media', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => 'Removable Media', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => 'Removable media other than floppy', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => 'Removable media other thanxfloppy', // translated
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
            array(
                'Type' => '<DVD Writer>', // not translated, but escaped
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
        );
        $filesystems = array(
            array(
                'Letter' => 'C:',
                'Label' => 'label1',
                'Type' => 'type1',
                'Filesystem' => 'filesystem1',
                'Size' => 10000,
                'UsedSpace' => 6000,
                'FreeSpace' => 4000,
            ),
            array(
                'Letter' => 'D:',
                'Label' => 'label2',
                'Type' => 'type2',
                'Filesystem' => 'filesystem2',
                'Size' => 0,
                'UsedSpace' => 6000, // ignored
                'FreeSpace' => 4000, // ignored
            ),
        );

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => $this->createMock('Model\Client\WindowsInstallation'),
            'Android' => null,
            'StorageDevice' => $devices,
            'Filesystem' => $filesystems,
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/storage/?id=1');
        $this->assertResponseStatusCode(200);
        // Devices
        $this->assertXpathQueryCount('//table[1]//th', 5);
        $this->assertXpathQueryContentContains('//table[1]/tr[2]/td[2]', "\nDVD-Brenner\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[3]/td[2]', "\nFestplatte\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[4]/td[2]', "\nFestplatte\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[5]/td[2]', "\nWechselmedium\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[6]/td[2]', "\nWechselmedium\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[7]/td[2]', "\nWechselmedium\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[8]/td[2]', "\n<DVD Writer>\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[2]/td[3]', "\n1,0\xC2\xA0GB\n");
        // Filesystem 1
        $this->assertXpathQuery("//table[2]//th[text()='\nBuchstabe\n']");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[5]', "\n9,8\xC2\xA0GB\n");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[6]', "\n5,9\xC2\xA0GB (60\xC2\xA0%)\n");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[7]', "\n3,9\xC2\xA0GB (40\xC2\xA0%)\n");
        // Filesystem 2
        $this->assertXpathQueryContentContains('//table[2]/tr[3]/td[5]', '');
        $this->assertXpathQueryContentContains('//table[2]/tr[3]/td[6]', '');
        $this->assertXpathQueryContentContains('//table[2]/tr[3]/td[7]', '');
    }

    public function testStorageActionUnix()
    {
        $devices = array(
            array(
                'ProductFamily' => 'product family',
                'ProductName' => 'product name',
                'Size' => 1024,
                'Device' => 'device',
                'Serial' => 'serial',
                'Firmware' => 'firmware',
            ),
        );
        $filesystems = array(
            array(
                'Mountpoint' => 'mountpoint1',
                'Device' => 'device1',
                'Filesystem' => 'filesystem1',
                'CreationDate' => new \DateTime('2014-05-31'),
                'Size' => 10000,
                'UsedSpace' => 6000,
                'FreeSpace' => 4000,
            ),
            array(
                'Mountpoint' => 'mountpoint2',
                'Device' => 'device2',
                'Filesystem' => 'filesystem2',
                'CreationDate' => null,
                'Size' => 10000,
                'UsedSpace' => 6000,
                'FreeSpace' => 4000,
            ),
        );

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'Android' => null,
            'StorageDevice' => $devices,
            'Filesystem' => $filesystems,
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/storage/?id=1');
        $this->assertResponseStatusCode(200);
        // Devices
        $this->assertXpathQueryCount('//table[1]//th', 6);
        // Filesystem 1
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[4]', "\n31.05.2014\n");
        // Filesystem 2
        $this->assertXpathQueryContentContains('//table[2]/tr[3]/td[4]', '');
    }

    public function testStorageActionAndroid()
    {
        $devices = [
            [
                'Type' => '_type',
                'Size' => 1024,
            ],
        ];
        $filesystems = [
            [
                'Mountpoint' => 'mountpoint2',
                'Device' => 'device2',
                'Filesystem' => 'filesystem2',
                'Size' => 10000,
                'UsedSpace' => 6000,
                'FreeSpace' => 4000,
            ],
        ];

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'Android' => $this->createMock('Model\Client\AndroidInstallation'),
            'StorageDevice' => $devices,
            'Filesystem' => $filesystems,
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/storage/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//table[1]//th', 2); // Devices
        $this->assertXpathQueryCount('//table[2]//th', 6); // Filesystems
    }

    public function testDisplayActionNoDisplays()
    {
        $displayControllers = array(
            array(
                'Name' => 'name1',
                'Chipset' => 'chipset1',
                'Memory' => 32,
                'CurrentResolution' => 'resolution1',
            ),
            array(
                'Name' => 'name2',
                'Chipset' => 'chipset2',
                'Memory' => null,
                'CurrentResolution' => 'resolution2',
            ),
        );

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'DisplayController' => $displayControllers,
            'Display' => array(),
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/display/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nDisplay-Controller\n']");
        $this->assertXpathQueryContentContains('//table/tr[2]/td[3]', "\n32\xC2\xA0MB\n");
        $this->assertXpathQueryContentContains('//table/tr[3]/td[3]', '');
        $this->assertNotXpathQuery("//h2[text()='\nDisplays\n']");
    }

    public function testDisplayActionDisplays()
    {
        $display = array(
            'Id' => 1,
            'Manufacturer' => 'manufacturer',
            'Description' => 'description',
            'Serial' => 'serial',
            'Edid' => 'EDID',
            'Type' => 'type',
        );

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'DisplayController' => array(),
            'Display' => array($display),
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/display/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nAnzeigegeräte\n']");
        $this->assertXpathQueryCount('//th', 5);
    }

    public function testBiosAction()
    {
        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'BiosManufacturer' => 'manufacturer',
            'BiosDate' => 'date',
            'BiosVersion' => 'line1;line2',
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/bios/?id=1');
        $this->assertResponseStatusCode(200);
        $query = "//table/tr/td[text()='\n%s\n']/following::td[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'Hersteller', 'manufacturer'));
        $this->assertXPathQuery(sprintf($query, 'Datum', 'date'));
        $this->assertXpathQueryContentContains('//table/tr[3]/td[2][name(node()[2])="br"]', "\nline1\nline2\n");
    }

    public function testSystemActionUnixNoSlots()
    {
        $controllers = array(
            array(
                'Type' => 'type',
                'Name' => 'name',
            ),
        );

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'MemorySlot' => array(),
            'Controller' => $controllers,
            'ExtensionSlot' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => $this->createMock('Model\Client\WindowsInstallation'),
            'MemorySlot' => array(),
            'Controller' => $controllers,
            'ExtensionSlot' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'MemorySlot' => $slots,
            'Controller' => array(),
            'ExtensionSlot' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'MemorySlot' => array(),
            'Controller' => array(),
            'ExtensionSlot' => $slots,
        ]);

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
        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'Printer' => $printers,
        ]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/printers/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//tr', 2);
    }

    public function testSoftwareActionWindows()
    {
        $hydrator = new ObjectPropertyHydrator();
        $software1 = $hydrator->hydrate([
            'name' => 'name1',
            'comment' => '',
            'version' => 'version1',
            'publisher' => 'publisher1',
            'installLocation' => 'location1',
            'architecture' => 32,
        ], new Software());
        $software2 = $hydrator->hydrate([
            'name' => 'name2',
            'comment' => '',
            'version' => 'version2',
            'publisher' => 'publisher2',
            'installLocation' => 'location2',
            'architecture' => null,
        ], new Software());

        $windows = $this->createStub(WindowsInstallation::class);

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([['windows', $windows]]);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software')
               ->willReturn(array($software1, $software2));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[normalize-space(text())='Version']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Herausgeber']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Ort']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Architektur']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Größe']");
        $this->assertXpathQuery("//tr[2]/td[5][normalize-space(text())='32\xC2\xA0Bit']");
        $this->assertXpathQuery("//tr[3]/td[5][normalize-space(text())='']");
    }

    public function testSoftwareActionUnix()
    {
        $hydrator = new ObjectPropertyHydrator();
        $software1 = $hydrator->hydrate([
            'name' => 'name1',
            'version' => 'version1',
            'size' => 42,
        ], new Software());

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software')
               ->willReturn(array($software1));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[normalize-space(text())='Version']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Herausgeber']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Ort']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Architektur']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Größe']");
        $this->assertXpathQuery("//tr[2]/td[3][@class='textright'][normalize-space(text())='42\xC2\xA0kB']");
    }

    public function testSoftwareActionAndroid()
    {
        $hydrator = new ObjectPropertyHydrator();
        $software1 = $hydrator->hydrate([
            'name' => 'name1',
            'version' => 'version1',
            'publisher' => 'publisher1',
            'installLocation' => 'location1',
        ], new Software());

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([
            ['windows', null],
            ['android', $this->createStub(AndroidInstallation::class)],
        ]);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software')
               ->willReturn([$software1]);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[normalize-space(text())='Version']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Herausgeber']");
        $this->assertXpathQuery("//th/a[normalize-space(text())='Ort']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Architektur']");
        $this->assertNotXpathQuery("//th/a[normalize-space(text())='Größe']");
    }

    public function testSoftwareActionComments()
    {
        $hydrator = new ObjectPropertyHydrator();
        $software1 = $hydrator->hydrate([
            'name' => 'name1',
            'comment' => 'comment1',
            'version' => 'version1',
            'size' => 0,
        ], new Software());
        $software2 = $hydrator->hydrate([
            'name' => 'name2',
            'comment' => '',
            'version' => 'version2',
            'size' => 0,
        ], new Software());

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software')
               ->willReturn(array($software1, $software2));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//tr[2]/td[1][@title="comment1"]');
        $this->assertNotXpathQuery('//tr[3]/td[1][title]');
    }

    public function testSoftwareActionDuplicates()
    {
        $hydrator = new ObjectPropertyHydrator();
        $software1a = $hydrator->hydrate([
            'name' => 'name',
            'version' => 'version1',
            'comment' => '',
            'publisher' => '',
            'installLocation' => '',
            'isHotfix' => null,
            'guid' => '',
            'language' => '',
            'installationDate' => new DateTime('2015-05-25'),
            'architecture' => null,
        ], new Software());
        $software2 = $hydrator->hydrate([
            'name' => 'name',
            'version' => 'version2',
            'comment' => '',
            'publisher' => '',
            'installLocation' => '',
            'isHotfix' => null,
            'guid' => '',
            'language' => '',
            'installationDate' => new DateTime('2015-05-25'),
            'architecture' => null,
        ], new Software());
        $software1b = $hydrator->hydrate([
            'name' => 'name',
            'version' => 'version1',
            'comment' => '',
            'publisher' => '',
            'installLocation' => '',
            'isHotfix' => null,
            'guid' => '',
            'language' => '',
            'installationDate' => new DateTime('2015-05-25'),
            'architecture' => null,
        ], new Software());

        $windows = $this->createStub(WindowsInstallation::class);

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([['windows', $windows]]);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software')
               ->willReturn(array($software1a, $software2, $software1b));
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//tr[2]/td[1]/span[@class="duplicate"][text()="(2)"]');
        $this->assertNotXpathQuery('//tr[3]/td[1]/span');
    }

    public function testSoftwareActionHideBlacklisted()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('displayBlacklistedSoftware')
                      ->willReturn(0);

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software', 'name', 'asc', ['Software.NotIgnored' => null])
               ->willReturn([]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
    }

    public function testSoftwareActionShowBlacklisted()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('displayBlacklistedSoftware')
                      ->willReturn(1);

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getItems')
               ->with('Software', 'name', 'asc', [])
               ->willReturn([]);

        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'AudioDevice' => array($audiodevice),
            'InputDevice' => array(),
            'Port' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'AudioDevice' => array(),
            'InputDevice' => $inputdevices,
            'Port' => array(),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => $this->createMock('Model\Client\WindowsInstallation'),
            'AudioDevice' => array(),
            'InputDevice' => array(),
            'Port' => array($port),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'AudioDevice' => array(),
            'InputDevice' => array(),
            'Port' => array($port),
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'CustomFields' => $customFields,
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null,
            'CustomFields' => $customFields,
        ]);

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

        $client = new Client([
            'Id' => 1,
            'Name' => 'name',
            'Windows' => null
        ]);
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

    public function testPackagesActionNoPackages()
    {
        $assignments = new ResultSet();
        $assignments->initialize(new EmptyIterator());

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getPackageAssignments')
               ->with('packageName', 'asc')
               ->willReturn($assignments);
        $client->expects($this->once())
               ->method('getAssignablePackages')
               ->willReturn([]);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//h2');
        $this->assertNotXpathQuery('//table');
        $this->assertNotXpathQuery('//form');
    }

    public function testPackagesActionAssigned()
    {
        $timestamp = new DateTime('2022-11-09T20:29:33');
        $hydrator = new ObjectPropertyHydrator();
        $assignments = [
            $hydrator->hydrate([
                'packageName' => 'package1',
                'status' => Assignment::PENDING,
                'timestamp' => $timestamp,
            ], new Assignment()),
            $hydrator->hydrate([
                'packageName' => 'package2',
                'status' => Assignment::RUNNING,
                'timestamp' => $timestamp,
            ], new Assignment()),
            $hydrator->hydrate([
                'packageName' => 'package3',
                'status' => Assignment::SUCCESS,
                'timestamp' => $timestamp,
            ], new Assignment()),
            $hydrator->hydrate([
                'packageName' => 'package4',
                'status' => '<ERROR>',
                'timestamp' => $timestamp,
            ], new Assignment()),
        ];

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([['id', 1]]);
        $client->expects($this->once())
               ->method('getPackageAssignments')
               ->with('packageName', 'asc')
               ->willReturn($assignments);
        $client->expects($this->once())
               ->method('getAssignablePackages')
               ->willReturn([]);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', 'Zugewiesene Pakete');
        $this->assertXpathQueryCount('//h2', 1);

        $this->assertXpathQueryContentContains('//tr[2]/td[2][@class="package_pending"]', 'Ausstehend');
        $this->assertXpathQueryContentContains('//tr[3]/td[2][@class="package_running"]', 'Laufend');
        $this->assertXpathQueryContentContains('//tr[4]/td[2][@class="package_success"]', 'Erfolg');
        $this->assertXpathQueryContentContains('//tr[5]/td[2][@class="package_error"]', '<ERROR>');

        $this->assertXpathQueryContentContains('//td', '09.11.22, 20:29');

        // Pending package must not have "reset" link
        $this->assertXpathQueryContentRegex('//tr[2]/td[4]', '/^\s*$/');
        // Other packages must have "reset" link
        $this->assertXpathQueryContentRegex(
            '//tr[3]/td[4]/a[@href="/console/client/resetpackage/?id=1&package=package2"]',
            '/zurücksetzen/'
        );
        $this->assertXpathQueryContentRegex(
            '//tr[4]/td[4]/a[@href="/console/client/resetpackage/?id=1&package=package3"]',
            '/zurücksetzen/'
        );
        $this->assertXpathQueryContentRegex(
            '//tr[5]/td[4]/a[@href="/console/client/resetpackage/?id=1&package=package4"]',
            '/zurücksetzen/'
        );

        // "remove" link
        $this->assertXpathQueryCount('//tr/td[5]/a', 4);
        $this->assertXpathQueryContentRegex(
            '//tr[3]/td[5]/a[@href="/console/client/removepackage/?id=1&package=package2"]',
            '/entfernen/'
        );
    }

    public function testPackagesActionAssignable()
    {
        $assignablePackages = ['package'];

        $assignments = new ResultSet();
        $assignments->initialize(new EmptyIterator());

        /** @var MockObject|Client */
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
               ->method('getPackageAssignments')
               ->with('packageName', 'asc')
               ->willReturn($assignments);
        $client->expects($this->once())
               ->method('getAssignablePackages')
               ->willReturn($assignablePackages);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', 'Pakete zuweisen');
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertXPathQuery('//form');
    }

    public function testGroupsActionNoGroups()
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(new \EmptyIterator());
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->never())
             ->method('render');
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->never())->method('getGroupMemberships');

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_groupManager->expects($this->once())
                           ->method('getGroups')
                           ->with(null, null, 'Name')
                           ->willReturn($resultSet);
        $this->dispatch('/console/client/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//h2');
        $this->assertNotXpathQuery('//table');
    }

    public function testGroupsActionOnlyExcluded()
    {
        $groups = array(
            array('Id' => 1, 'Name' => 'group1'),
            array('Id' => 2, 'Name' => 'group2'),
        );
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize($groups);
        $formGroups = array(
            'group1' => \Model\Client\Client::MEMBERSHIP_NEVER,
            'group2' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $form->expects($this->once())
             ->method('setData')
             ->with(array('Groups' => $formGroups));
        $form->expects($this->once())
             ->method('setAttribute')
             ->with('action', '/console/client/managegroups/?id=1');
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())
               ->method('getGroupMemberships')
               ->with(\Model\Client\Client::MEMBERSHIP_ANY)
               ->willReturn(array(1 => \Model\Client\Client::MEMBERSHIP_NEVER));
        $client->method('offsetGet')
               ->will($this->returnValueMap(array(array('Id', 1))));
        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name')
                            ->willReturn($resultSet);
        $this->dispatch('/console/client/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nMitgliedschaften verwalten\n");
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertNotXpathQuery('//table');
        $this->assertXPathQuery('//form');
    }

    public function testGroupsActionMember()
    {
        $groups = array(
            array('Id' => 1, 'Name' => 'group1'),
            array('Id' => 2, 'Name' => 'group2'),
        );
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize($groups);
        $memberships = array(
            1 => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            2 => \Model\Client\Client::MEMBERSHIP_ALWAYS,
        );
        $formGroups = array(
            'group1' => \Model\Client\Client::MEMBERSHIP_AUTOMATIC,
            'group2' => \Model\Client\Client::MEMBERSHIP_ALWAYS,
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $form->expects($this->once())
             ->method('setData')
             ->with(array('Groups' => $formGroups));
        $form->expects($this->once())
             ->method('setAttribute')
             ->with('action', '/console/client/managegroups/?id=1');
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())
               ->method('getGroupMemberships')
               ->with(\Model\Client\Client::MEMBERSHIP_ANY)
               ->willReturn($memberships);
        $client->method('offsetGet')
               ->will($this->returnValueMap(array(array('Id', 1))));
        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name')
                            ->willReturn($resultSet);
        $this->dispatch('/console/client/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nGruppenmitgliedschaften\n");
        $this->assertXpathQueryContentContains(
            '//tr[2]/td[1]/a[@href="/console/group/general/?name=group1"]',
            'group1'
        );
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\nautomatisch\n");
        $this->assertXpathQueryContentContains(
            '//tr[3]/td[1]/a[@href="/console/group/general/?name=group2"]',
            'group2'
        );
        $this->assertXpathQueryContentContains('//tr[3]/td[2]', "\nmanuell\n");
        $this->assertXpathQueryContentContains('//h2', "\nMitgliedschaften verwalten\n");
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionGet()
    {
        $config = array('name' => 'value');
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('getAllConfig')->willReturn($config);
        $this->_clientManager->method('getClient')->willReturn($client);
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($client);
        $form->expects($this->once())
             ->method('setData')
             ->with($config);
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('process');

        $formHelper = $this->createMock(ClientConfig::class);
        $formHelper->method('__invoke')->with($form)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormClientConfig', $formHelper);

        $this->dispatch('/console/client/configuration/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $client = $this->createMock('Model\Client\Client');
        $this->_clientManager->method('getClient')->willReturn($client);
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($client);
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('process');

        $formHelper = $this->createMock(ClientConfig::class);
        $formHelper->method('__invoke')->with($form)->willReturn('<form></form>');
        $this->getApplicationServiceLocator()
             ->get('ViewHelperManager')
             ->setService('consoleFormClientConfig', $formHelper);

        $this->dispatch('/console/client/configuration/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostValid()
    {
        $postData = array('key' => 'value');

        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap(array(array('Id', 1))));
        $this->_clientManager->method('getClient')->willReturn($client);

        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($client);
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('process');

        $this->dispatch('/console/client/configuration/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/configuration/?id=1');
    }

    public function testDeleteActionGet()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\DeleteClient');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $map = array(
            array('Name', 'name'),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_clientManager->expects($this->never())->method('deleteClient');

        $this->dispatch('/console/client/delete/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\nClient 'name' wird dauerhaft gelöscht. Fortfahren?\n"
        );
        $this->assertXPathQuery('//form');
    }

    public function testDeleteActionPostNo()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_clientManager->expects($this->never())->method('deleteClient');

        $this->dispatch('/console/client/delete/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/client/general/?id=1');
    }

    public function testDeleteActionPostYesDeleteInterfacesSuccess()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Name', 'name'),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_clientManager->expects($this->once())->method('deleteClient')->with($client, true);

        $postData = array('yes' => 'Yes', 'DeleteInterfaces' => '1');
        $this->dispatch('/console/client/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
        $flashMessenger = $this->getControllerPlugin('FlashMessenger');
        $this->assertEquals(
            ["Client 'name' wurde erfolgreich gelöscht."],
            $flashMessenger->getCurrentSuccessMessages()
        );
        $this->assertEmpty($flashMessenger->getCurrentErrorMessages());
    }

    public function testDeleteActionPostYesKeepInterfacesError()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Name', 'name'),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));

        $this->_clientManager->method('getClient')->willReturn($client);
        $this->_clientManager->expects($this->once())
                             ->method('deleteClient')
                             ->with($client, false)
                             ->willThrowException(new \RuntimeException());

        $postData = array('yes' => 'Yes', 'DeleteInterfaces' => '0');
        $this->dispatch('/console/client/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
        $flashMessenger = $this->getControllerPlugin('FlashMessenger');
        $this->assertEmpty($flashMessenger->getCurrentSuccessMessages());
        $this->assertEquals(
            ["Client 'name' konnte nicht gelöscht werden."],
            $flashMessenger->getCurrentErrorMessages()
        );
    }

    public function testRemovepackageActionGet()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->never())->method('removePackage');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/removepackage/?id=1&package=name');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p',
            "Paket 'name' wird nicht mehr diesem Client zugewiesen sein. Fortfahren?"
        );
    }

    public function testRemovepackageActionPostNo()
    {
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->never())->method('removePackage');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/removepackage/?id=1&package=name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testRemovepackageActionPostYes()
    {
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->once())->method('removePackage')->with('name');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/removepackage/?id=1&package=name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testAssignpackageActionGet()
    {
        $client = new Client();
        $client->id = 1;
        $this->_clientManager->method('getClient')->willReturn($client);

        /** @var MockObject|AssignPackagesForm */
        $form = $this->createMock(AssignPackagesForm::class);
        $form->expects($this->never())->method('process');
        $this->getApplicationServiceLocator()->setService(AssignPackagesForm::class, $form);

        $this->dispatch('/console/client/assignpackage/?id=1', 'GET');
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testAssignpackageActionPost()
    {
        $postData = ['packages' => ['package1', 'package2']];

        $client = new Client();
        $client->id = 1;
        $this->_clientManager->method('getClient')->willReturn($client);

        /** @var MockObject|AssignPackagesForm */
        $form = $this->createMock(AssignPackagesForm::class);
        $form->expects($this->once())->method('process')->with($postData, $client);
        $this->getApplicationServiceLocator()->setService(AssignPackagesForm::class, $form);

        $this->dispatch('/console/client/assignpackage/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testResetpackageActionGet()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->never())->method('resetPackage');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/resetpackage/?id=1&package=name');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p',
            "Der Status des Pakets 'name' wird auf 'Ausstehend' zurückgesetzt. Fortfahren?"
        );
    }

    public function testResetpackageActionPostNo()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->with('Id')->willReturn(1);
        $client->expects($this->never())->method('resetPackage');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/resetpackage/?id=1&package=name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testResetpackageActionPostYes()
    {
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->with('Id')->willReturn(1);
        $client->expects($this->once())->method('resetPackage')->with('name');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/resetpackage/?id=1&package=name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testManagegroupsActionGet()
    {
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->never())
             ->method('isValid');
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->never())->method('setGroupMemberships');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/managegroups/?id=1');
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testManagegroupsActionPostInvalid()
    {
        $postData = array(
            'Groups' => array('group1' => '1', 'group2' => '2')
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->never())->method('setGroupMemberships');
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testManagegroupsActionPostValid()
    {
        $postData = array(
            'Groups' => array('group1' => '1', 'group2' => '2')
        );
        $form = $this->getApplicationServiceLocator()->get('FormElementManager')->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($postData));
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $map = array(
            array('Id', 1),
        );
        $client = $this->createMock('Model\Client\Client');
        $client->method('offsetGet')->will($this->returnValueMap($map));
        $client->expects($this->once())->method('setGroupMemberships')->with($postData['Groups']);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testSearchActionNoPreset()
    {
        $serviceManager = $this->getApplicationServiceLocator();

        $form = $serviceManager->get('FormElementManager')->get(SearchForm::class);
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->once())
             ->method('remove')
             ->with('_csrf');
        $form->expects($this->exactly(2))
             ->method('setAttribute')
             ->with(
                 $this->matchesRegularExpression('#^(method|action)$#'),
                 $this->matchesRegularExpression('#^(GET|/console/client/index/)$#')
             );


        $viewModel = new ViewModel();

        $printForm = $this->createMock(PrintForm::class);
        $printForm->method('__invoke')->with($form, SearchHelper::class)->willReturn($viewModel);

        $serviceManager->get('ControllerPluginManager')->setService('printForm', $printForm);

        $this->interceptRenderEvent();
        $this->dispatch('/console/client/search/');
        $this->assertResponseStatusCode(200);
        $this->assertMvcResult($viewModel);
    }

    public function testSearchActionPreset()
    {
        $serviceManager = $this->getApplicationServiceLocator();

        $form = $serviceManager->get('FormElementManager')->get(SearchForm::class);
        $form->expects($this->once())
             ->method('setData')
             ->with(['filter' => 'Name', 'search' => 'value']);
        $form->expects($this->once())
             ->method('isValid');
        $form->expects($this->once())
             ->method('remove')
             ->with('_csrf');
        $form->expects($this->exactly(2))
             ->method('setAttribute')
             ->with(
                 $this->matchesRegularExpression('#^(method|action)$#'),
                 $this->matchesRegularExpression('#^(GET|/console/client/index/)$#')
             );

        $viewModel = new ViewModel();

        $printForm = $this->createMock(PrintForm::class);
        $printForm->method('__invoke')->with($form, SearchHelper::class)->willReturn($viewModel);

        $serviceManager->get('ControllerPluginManager')->setService('printForm', $printForm);

        $this->interceptRenderEvent();
        $this->dispatch('/console/client/search/?filter=Name&search=value');
        $this->assertResponseStatusCode(200);
        $this->assertMvcResult($viewModel);
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

    public function testExportAction()
    {
        $xmlContent = "xml_content\n";
        $document = $this->createMock('\Protocol\Message\InventoryRequest');
        $document->expects($this->once())
                 ->method('getFilename')
                 ->will($this->returnValue('filename.xml'));
        $document->expects($this->once())
                 ->method('saveXml')
                 ->will($this->returnValue($xmlContent));
        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('toDomDocument')->willReturn($document);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/export/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');
        $this->assertResponseHeaderContains('Content-Disposition', 'attachment; filename="filename.xml"');
        $this->assertResponseHeaderContains('Content-Length', (string) strlen($xmlContent));
        $this->assertEquals($xmlContent, $this->getResponse()->getContent());
    }

    public function testExportActionNoValidate()
    {
        $this->_config->expects($this->once())->method('__get')->with('validateXml')->willReturn('0');

        $document = $this->createMock('\Protocol\Message\InventoryRequest');
        $document->expects($this->never())->method('forceValid');

        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('toDomDocument')->willReturn($document);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/export/?id=1');
        $this->assertResponseStatusCode(200);
    }

    public function testExportActionValidate()
    {
        $this->_config->expects($this->once())->method('__get')->with('validateXml')->willReturn('1');

        $document = $this->createMock('\Protocol\Message\InventoryRequest');
        $document->expects($this->once())->method('forceValid');

        $client = $this->createMock('Model\Client\Client');
        $client->expects($this->once())->method('toDomDocument')->willReturn($document);
        $this->_clientManager->method('getClient')->willReturn($client);

        $this->dispatch('/console/client/export/?id=1');
        $this->assertResponseStatusCode(200);
    }
}
