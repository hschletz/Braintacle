<?php
/**
 * Tests for ClientController
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
 * Tests for ClientController
 */
class ClientControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Computer mock
     * @var \Model_Computer
     */
    protected $_computer;

    /**
     * Group manager mock
     * @var \Model\Group\GroupManager
     */
    protected $_groupManager;

    /**
     * Software manager mock
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Form manager mock
     * @var \Zend\Form\FormElementManager
     */
    protected $_formManager;

    /**
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    protected $_inventoryUploader;

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
            'UserDefinedInfo.customField' => '<custom1>',
            'UserDefinedInfo.TAG' => 'category1',
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
            'UserDefinedInfo.customField' => '<custom2>',
            'UserDefinedInfo.TAG' => 'category2',
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

    public function setUp()
    {
        $this->_computer = $this->getMockBuilder('Model_Computer')->disableOriginalConstructor()->getMock();
        $this->_groupManager = $this->getMockBuilder('Model\Group\GroupManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->_softwareManager = $this->getMockBuilder('Model\SoftwareManager')
                                       ->disableOriginalConstructor()
                                       ->getMock();
        $this->_formManager = new \Zend\Form\FormElementManager;
        $this->_formManager->setService('Console\Form\Package\Assign', $this->getMock('Console\Form\Package\Assign'));
        $this->_formManager->setService('Console\Form\ClientConfig', $this->getMock('Console\Form\ClientConfig'));
        $this->_formManager->setService('Console\Form\CustomFields', $this->getMock('Console\Form\CustomFields'));
        $this->_formManager->setService('Console\Form\DeleteClient', $this->getMock('Console\Form\DeleteClient'));
        $this->_formManager->setService(
            'Console\Form\GroupMemberships',
            $this->getMock('Console\Form\GroupMemberships')
        );
        $this->_formManager->setService('Console\Form\Import', $this->getMock('Console\Form\Import'));
        $this->_formManager->setService('Console\Form\ProductKey', $this->getMock('Console\Form\ProductKey'));
        $this->_formManager->setService('Console\Form\Search', $this->getMock('Console\Form\Search'));

        $this->_config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $this->_inventoryUploader = $this->getMockBuilder('\Library\InventoryUploader')
                                         ->disableOriginalConstructor()
                                         ->getMock();

        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\ClientController(
            $this->_computer,
            $this->_groupManager,
            $this->_softwareManager,
            $this->_formManager,
            $this->_config,
            $this->_inventoryUploader
        );
    }

    public function testService()
    {
        $this->_overrideService('Model\Computer\Computer', $this->_computer);
        $this->_overrideService('Model\Config', $this->_config);
        parent::testService();
    }

    public function testInvalidClient()
    {
        $this->_computer->expects($this->once())
                        ->method('fetchById')
                        ->with(42)
                        ->will($this->throwException(new \RuntimeException));
        $this->dispatch('/console/client/general/?id=42');
        $this->assertRedirectTo('/console/client/index/');
        $this->assertContains(
            'The requested client does not exist.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testMenuForWindowsClients()
    {
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will(
                            $this->returnValueMap(
                                array(
                                    array('Windows', $this->getMock('Model\Client\WindowsInstallation')),
                                )
                            )
                        );
        $this->dispatch('/console/client/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertXpathQuery($query . '/a[@href="/console/client/windows/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/client/msoffice/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/client/registry/?id=1"]');
    }

    public function testMenuForNonWindowsClients()
    {
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will(
                            $this->returnValueMap(
                                array(
                                    array('Windows', null),
                                )
                            )
                        );
        $this->dispatch('/console/client/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/windows/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/msoffice/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/client/registry/?id=1"]');
    }

    public function testIndexActionWithoutParams()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            $this->_defaultColumns,
                            'InventoryDate',
                            'desc',
                            null,
                            null,
                            null,
                            null
                        )
                        ->will($this->returnValue($this->_sampleClients));
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
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'InventoryDate'),
                            'InventoryDate',
                            'desc',
                            null,
                            null,
                            null,
                            null
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch('/console/client/index/?columns=Name,InventoryDate');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//th', 2);
    }

    public function testIndexActionWithValidJumpto()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch('/console/client/index/?jumpto=software');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/client/software/?id=2"]',
            'name2'
        );
    }

    public function testIndexActionWithInvalidJumpto()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch('/console/client/index/?jumpto=invalid');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/client/general/?id=2"]',
            'name2'
        );
    }

    public function testIndexActionWithBuiltinSingleFilter()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            $this->_defaultColumns,
                            'InventoryDate',
                            'desc',
                            'PackageError',
                            'packageName',
                            null,
                            null
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch('/console/client/index/?filter=PackageError&search=packageName');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Computer, bei denen die Installation von Paket 'packageName' fehlgeschlagen ist\n"
        );
    }

    public function testIndexActionWithBuiltinMultiFilter()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            $this->_defaultColumns,
                            'InventoryDate',
                            'desc',
                            array('NetworkInterface.Subnet', 'NetworkInterface.Netmask'),
                            array('192.0.2.0', '255.255.255.0'),
                            array(null, null),
                            array(null, null)
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch(
            '/console/client/index/?' .
            'filter1=NetworkInterface.Subnet&search1=192.0.2.0&' .
            'filter2=NetworkInterface.Netmask&search2=255.255.255.0'
        );
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Computer mit Interface in Netzwerk 192.0.2.0/24\n"
        );
    }

    public function testIndexActionWithBuiltinSoftwareFilter()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->never())
             ->method('setData');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            $this->_defaultColumns,
                            'InventoryDate',
                            'desc',
                            'Software',
                            "\xc2\x99", // Incorrect representation of TM symbol
                            null,
                            null
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $this->dispatch('/console/client/index/?filter=Software&search=%C2%99');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\n2 Computer, auf denen die Software '\xe2\x84\xa2' installiert ist\n"
        ); // Corrected representation of TM symbol
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
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate'),
                            'InventoryDate',
                            'desc',
                            'Name',
                            'test',
                            'eq',
                            '1'
                        )
                        ->will($this->returnValue($this->_sampleClients));
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
        $form = $this->_formManager->get('Console\Form\Search');
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
                        'search' => new \Zend_Date('2014-05-12'),
                        'operator' => 'eq',
                        'invert' => '1',
                        'customSearch' => 'button',
                     )
                 )
             );
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate'),
                            'InventoryDate',
                            'desc',
                            'InventoryDate',
                            new \Zend_Date('2014-05-12'),
                            'eq',
                            '1'
                        )
                        ->will($this->returnValue($this->_sampleClients));
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
        $form = $this->_formManager->get('Console\Form\Search');
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
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate'),
                            'InventoryDate',
                            'desc',
                            'CpuType',
                            'value',
                            'eq',
                            '0'
                        )
                        ->will($this->returnValue(array()));
        $query = 'filter=CpuType&search=value&operator=eq&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionWithCustomNonEqualitySearchOnNonDefaultColumn()
    {
        // Non-equality search should add the searched column.
        $form = $this->_formManager->get('Console\Form\Search');
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
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'CpuType'),
                            'InventoryDate',
                            'desc',
                            'CpuType',
                            'value',
                            'ne',
                            '0'
                        )
                        ->will($this->returnValue(array()));
        $query = 'filter=CpuType&search=value&operator=ne&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
    }

    public function testIndexActionWithCustomInvertedEqualitySearchOnNonDefaultColumn()
    {
        // Inverted equality search should add the searched column.
        $form = $this->_formManager->get('Console\Form\Search');
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
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'CpuType'),
                            'InventoryDate',
                            'desc',
                            'CpuType',
                            'value',
                            'eq',
                            '1'
                        )
                        ->will($this->returnValue(array()));
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
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'Registry.value'),
                            'InventoryDate',
                            'desc',
                            'Registry.value',
                            'test',
                            'like',
                            '0'
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $query = 'filter=Registry.value&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "value");
    }

    public function testIndexActionWithCustomSearchOnCustomFieldText()
    {
        $formData = array(
            'filter' => 'UserDefinedInfo.customField',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'UserDefinedInfo.customField'),
                            'InventoryDate',
                            'desc',
                            'UserDefinedInfo.customField',
                            'test',
                            'like',
                            '0'
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $query = 'filter=UserDefinedInfo.customField&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "customField");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\n<custom1>\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\n<custom2>\n");
    }

    public function testIndexActionWithCustomSearchOnCustomFieldDate()
    {
        $formData = array(
            'filter' => 'UserDefinedInfo.customField',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->_formManager->get('Console\Form\Search');
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
        $sampleClients[0]['UserDefinedInfo.customField'] = new \Zend_Date('2015-04-11 10:31:00');
        $sampleClients[1]['UserDefinedInfo.customField'] = new \Zend_Date('2015-04-12 10:32:00');
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'UserDefinedInfo.customField'),
                            'InventoryDate',
                            'desc',
                            'UserDefinedInfo.customField',
                            'test',
                            'like',
                            '0'
                        )
                        ->willReturn($sampleClients);
        $query = 'filter=UserDefinedInfo.customField&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "customField");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\n11.04.2015\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\n12.04.2015\n");
    }

    public function testIndexActionWithCustomSearchOnCategory()
    {
        $formData = array(
            'filter' => 'UserDefinedInfo.TAG',
            'search' => 'test',
            'operator' => 'like',
            'invert' => '0',
            'customSearch' => 'button',
        );
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with($formData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($formData));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->with(
                            array('Name', 'UserName', 'InventoryDate', 'UserDefinedInfo.TAG'),
                            'InventoryDate',
                            'desc',
                            'UserDefinedInfo.TAG',
                            'test',
                            'like',
                            '0'
                        )
                        ->will($this->returnValue($this->_sampleClients));
        $query = 'filter=UserDefinedInfo.TAG&search=test&operator=like&invert=0';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "Kategorie");
        $this->assertXpathQueryContentContains('//tr[2]/td[4]', "\ncategory1\n");
        $this->assertXpathQueryContentContains('//tr[3]/td[4]', "\ncategory2\n");
    }

    public function testIndexActionWithInvalidCustomSearch()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('getData');
        $this->_computer->expects($this->never())
                        ->method('fetch');
        $query = 'filter=CpuClock&search=invalid&operator=lt&invert=1';
        $this->dispatch("/console/client/index/?customSearch=button&$query");
        $this->assertRedirectTo("/console/client/search/?customSearch=button&$query");
    }

    public function testIndexActionMessages()
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addErrorMessage('error');
        $flashMessenger->addSuccessMessage(array('success %d' => 42));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->will($this->returnValue(array()));
        $this->_disableTranslator();
        $this->dispatch('/console/client/index/');
        $this->assertXpathQuery('//ul[@class="error"]/li[text()="error"]');
        $this->assertXpathQuery('//ul[@class="success"]/li[text()="success 42"]');
    }

    public function testGeneralActionDefault()
    {
        $map = array(
            array('Id', 1),
            array('ClientId', 'client_id'),
            array('InventoryDate', new \Zend_Date('2014-05-29 11:16:15')),
            array('LastContactDate', new \Zend_Date('2014-05-29 11:17:34')),
            array('OcsAgent', 'user_agent'),
            array('Manufacturer', 'manufacturer'),
            array('Model', 'model'),
            array('IsSerialBlacklisted', false),
            array('Serial', 'serial'),
            array('IsAssetTagBlacklisted', false),
            array('AssetTag', 'asset_tag'),
            array('Type', 'type'),
            array('OsName', 'os_name'),
            array('OsVersionString', 'os_version_string'),
            array('OsVersionNumber', 'os_version_number'),
            array('OsComment', 'os_comment'),
            array('CpuType', 'cpu_type'),
            array('CpuClock', 1234),
            array('CpuCores', 2),
            array('MemorySlot', array(array('Size' => 2), array('Size' => 3))),
            array('PhysicalMemory', 1234),
            array('SwapMemory', 5678),
            array('UserName', 'user_name'),
            array('Windows', null),
            array('Uuid', 'uuid'),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/general/?id=1');
        $this->assertResponseStatusCode(200);

        $query = "//dl/dt[text()='\n%s\n']/following::dd[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'ID', 1));
        $this->assertXPathQuery(sprintf($query, 'Client-ID', 'client_id'));
        $this->assertXPathQuery(sprintf($query, 'Datum der Inventarinformationen', '29.05.2014 11:16:15'));
        $this->assertXPathQuery(sprintf($query, 'Letzter Kontakt', '29.05.2014 11:17:34'));
        $this->assertXPathQuery(sprintf($query, 'User-Agent', 'user_agent'));
        $this->assertXPathQuery(sprintf($query, 'Modell', 'manufacturer model'));
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
        $this->assertXpathQuery("//dd[text()='\nserial\n'][not(@class)]");
        $this->assertXpathQuery("//dd[text()='\nasset_tag\n'][not(@class)]");
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQuery("//dd[text()='\nserial\n'][@class='blacklisted']");
        $this->assertXpathQuery("//dd[text()='\nasset_tag\n'][not(@class)]");
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQuery("//dd[text()='\nserial\n'][not(@class)]");
        $this->assertXpathQuery("//dd[text()='\nasset_tag\n'][@class='blacklisted']");
    }

    public function testGeneralActionWindowsUser()
    {
        $map = array(
            array('UserName', 'user_name'),
            array('Windows', array('UserDomain' => 'user_domain')),
            array('MemorySlot', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/general/?id=1');
        $this->assertXpathQueryContentContains('//dd', "\nuser_name @ user_domain\n");
    }

    public function testWindowsActionGet()
    {
        // Since form elements are rendered manually, mocking the entire form
        // would be very complicated. Just stub the pivotal methods and leave
        // elements as is.
        $form = $this->getMockBuilder('Console\Form\ProductKey')
                     ->setMethods(array('isValid', 'prepare', 'setData'))
                     ->getMock();
        $form->expects($this->once())
             ->method('setData')
             ->with(array('Key' => 'manual_product_key'));
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->once())
             ->method('prepare');
        $form->init();
        $this->_formManager = new \Zend\Form\FormElementManager;
        $this->_formManager->setService('Console\Form\ProductKey', $form);
        $windows = array(
            'Company' => 'company',
            'Owner' => 'owner',
            'ProductId' => 'product_id',
            'ProductKey' => 'product_key',
            'ManualProductKey' => 'manual_product_key',
        );
        $this->_softwareManager->expects($this->never())->method('setProductKey');
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap(array(array('Windows', $windows))));
        $this->dispatch('/console/client/windows/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form[@action=""][@method="POST"]');
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
        $postData = array('key' => 'entered_key');
        // Again, just stub the pivotal methods
        $form = $this->getMockBuilder('Console\Form\ProductKey')
                     ->setMethods(array('isValid', 'prepare', 'setData'))
                     ->getMock();
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->once())
             ->method('prepare');
        $form->init();
        $form->get('Key')->setMessages(array('message'));
        $this->_formManager = new \Zend\Form\FormElementManager;
        $this->_formManager->setService('Console\Form\ProductKey', $form);
        $this->_softwareManager->expects($this->never())->method('setProductKey');
        $this->dispatch('/console/client/windows/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//*[@class="error"]//*[text()="message"]');
    }

    public function testWindowsActionPostValid()
    {
        $postData = array('Key' => 'entered_key');
        $form = $this->_formManager->get('Console\Form\ProductKey');
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));

        $this->_softwareManager->expects($this->once())
                               ->method('setProductKey')
                               ->with($this->_computer, 'entered_key');

        $this->dispatch('/console/client/windows/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/windows/?id=1');
    }

    public function testNetworkActionSettingsOnly()
    {
        // DnsServer and DefaultGateway typically show up both or not at all, so
        // they are not tested separately.
        $map = array(
            array('DnsServer', 'dns_server'),
            array('DefaultGateway', 'default_gateway'),
            array('NetworkInterface', array()),
            array('Modem', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nGlobale Netzwerkkonfiguration\n']");
        $query = "//td[text()='\n%s\n']/following::td[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'DNS-Server', 'dns_server'));
        $this->assertXPathQuery(sprintf($query, 'Standardgateway', 'default_gateway'));
        $this->assertNotXpathQuery("//h2[text()='\nNetzwerkschnittstellen\n']");
        $this->assertNotXpathQuery("//h2[text()='\nModems\n']");
    }

    public function testNetworkActionInterfacesOnly()
    {
        $macAddress = $this->getMockBuilder('Library\MacAddress')->disableOriginalConstructor()->getMock();
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
        $map = array(
            array('NetworkInterface', $interfaces),
            array('Modem', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $map = array(
            array('NetworkInterface', array()),
            array('Modem', array($modem)),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
                'Name' => 'name',
                'Size' => 1024,
            ),
            array(
                'Type' => 'Hard disk', // translated
                'Name' => 'name',
                'Size' => 1024,
            ),
            array(
                'Type' => 'Removable medium', // translated
                'Name' => 'name',
                'Size' => 1024,
            ),
            array(
                'Type' => 'Floppy disk drive', // translated
                'Name' => 'name',
                'Size' => 1024,
            ),
            array(
                'Type' => '<other>', // not translated, but escaped
                'Name' => 'name',
                'Size' => 1024,
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
        $map = array(
            array('Windows', $this->getMock('Model\Client\WindowsInstallation')),
            array('StorageDevice', $devices),
            array('Volume', $filesystems),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/storage/?id=1');
        $this->assertResponseStatusCode(200);
        // Devices
        $this->assertXpathQueryCount('//table[1]//th', 3);
        $this->assertXpathQueryContentContains('//table[1]/tr[2]/td[1]', "\nDVD-Brenner\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[3]/td[1]', "\nFestplatte\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[4]/td[1]', "\nWechselmedium\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[5]/td[1]', "\nDiskettenlaufwerk\n");
        $this->assertXpathQueryContentContains('//table[1]/tr[6]/td[1]', "\n<other>\n");
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
                'Type' => 'type',
                'Name' => 'name',
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
                'CreationDate' => new \Zend_Date('2014-05-31'),
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
        $map = array(
            array('Windows', null),
            array('StorageDevice', $devices),
            array('Volume', $filesystems),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/storage/?id=1');
        $this->assertResponseStatusCode(200);
        // Devices
        $this->assertXpathQueryCount('//table[1]//th', 6);
        // Filesystem 1
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[4]', "\n31.05.2014\n");
        // Filesystem 2
        $this->assertXpathQueryContentContains('//table[2]/tr[3]/td[4]', '');
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
        $map = array(
            array('DisplayController', $displayControllers),
            array('Display', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
            'Manufacturer' => 'manufacturer',
            'Description' => 'description',
            'Serial' => 'serial',
            'ProductionDate' => 'date',
            'Type' => 'type',
        );
        $map = array(
            array('DisplayController', array()),
            array('Display', array($display)),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/display/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nAnzeigegeräte\n']");
        $this->assertXpathQueryCount('//th', 5);
    }

    public function testBiosAction()
    {
        $map = array(
            array('BiosManufacturer', 'manufacturer'),
            array('BiosDate', 'date'),
            array('BiosVersion', 'line1;line2'),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/bios/?id=1');
        $this->assertResponseStatusCode(200);
        $query = "//dl/dt[text()='\n%s\n']/following::dd[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'Hersteller', 'manufacturer'));
        $this->assertXPathQuery(sprintf($query, 'Datum', 'date'));
        $this->assertXpathQueryContentContains('//dd[3][name(node()[2])="br"]', "\nline1\nline2\n");
    }

    public function testSystemActionUnixNoSlots()
    {
        $controllers = array(
            array(
                'Type' => 'type1',
                'Manufacturer' => 'manufacturer1',
                'Name' => 'name',
                'Comment' => 'comment',
            ),
            array(
                'Type' => 'type2',
                'Manufacturer' => 'manufacturer2',
                'Name' => 'name_equals_comment',
                'Comment' => 'name_equals_comment',
            ),
        );
        $map = array(
            array('MemorySlot', array()),
            array('Controller', $controllers),
            array('ExtensionSlot', array()),
            array('Windows', null),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nRAM-Steckplätze\n']");
        $this->assertNotXpathQuery("//h2[text()='\nErweiterungssteckplätze\n']");
        $this->assertXpathQuery("//h2[text()='\nController\n']");
        $this->assertNotXpathQuery("//th[text()='\nTreiber-Version\n']");
        $this->assertXpathQueryCount('//span[@title]', 1);
        $this->assertXpathQuery("//span[@title='comment'][text()='\nname\n']");
    }

    public function testSystemActionWindows()
    {
        $controllers = array(
            array(
                'Type' => 'type',
                'Manufacturer' => 'manufacturer',
                'Name' => 'name',
                'Comment' => 'comment',
                'DriverVersion' => 'driver',
            ),
        );
        $map = array(
            array('MemorySlot', array()),
            array('Controller', $controllers),
            array('ExtensionSlot', array()),
            array('Windows', $this->getMock('Model\Client\WindowsInstallation')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th[text()='\nTreiber-Version\n']");
        $this->assertXpathQuery("//td[text()='\ndriver\n']");
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
                'Purpose' => 'purpose1',
            ),
            array(
                'SlotNumber' => 1,
                'Size' => 0,
                'Type' => 'type1',
                'Clock' => 'invalid',
                'Serial' => 'serial1',
                'Caption' => 'caption1',
                'Description' => 'description1',
                'Purpose' => 'purpose1',
            ),
        );
        $map = array(
            array('MemorySlot', $slots),
            array('Controller', array()),
            array('ExtensionSlot', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nRAM-Steckplätze\n']");
        $this->assertXPathQuery("//tr[2]/td[2][text()='\n1024\xC2\xA0MB\n']");
        $this->assertXPathQuery("//tr[2]/td[4][text()='\n333\xC2\xA0MHz\n']");
        $this->assertXPathQuery("//tr[3]/td[2][text()='\n\n']");
        $this->assertXPathQuery("//tr[3]/td[4][text()='\ninvalid\n']");
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
        $map = array(
            array('MemorySlot', array()),
            array('Controller', array()),
            array('ExtensionSlot', $slots),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $map = array(
            array('Printer', $printers),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/client/printers/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//tr', 2);
    }

    public function testSoftwareActionWindows()
    {
        $software1 = array(
            'Name' => 'name1',
            'Comment' => '',
            'Version' => 'version1',
            'Publisher' => 'publisher1',
            'InstallLocation' => 'location1',
            'Architecture' => '32',
        );
        $software2 = array(
            'Name' => 'name2',
            'Comment' => '',
            'Version' => 'version2',
            'Publisher' => 'publisher2',
            'InstallLocation' => 'location2',
            'Architecture' => '',
        );
        $softwareModel = $this->getMock('Model_Software');
        $softwareModel->expects($this->any())
                      ->method('getArrayCopy')
                      ->will($this->onConsecutiveCalls($software1, $software2));
        $map = array(
            array('Windows', $this->getMock('Model\Client\WindowsInstallation')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->will($this->returnValue(array($softwareModel, $softwareModel)));
        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[text()='Version']");
        $this->assertXpathQuery("//th/a[text()='Herausgeber']");
        $this->assertXpathQuery("//th/a[text()='Ort']");
        $this->assertXpathQuery("//th/a[text()='Architektur']");
        $this->assertNotXpathQuery("//th/a[text()='Größe']");
        $this->assertXpathQuery("//tr[2]/td[5][text()='\n32 Bit\n']");
        $this->assertXpathQuery("//tr[3]/td[5][text()='\n\n']");
    }

    public function testSoftwareActionUnix()
    {
        $software1 = array(
            'Name' => 'name1',
            'Comment' => '',
            'Version' => 'version1',
            'Size' => 42,
        );
        $softwareModel = $this->getMock('Model_Software');
        $softwareModel->expects($this->any())
                      ->method('getArrayCopy')
                      ->will($this->onConsecutiveCalls($software1));
        $map = array(
            array('Windows', null),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->will($this->returnValue(array($softwareModel)));
        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[text()='Version']");
        $this->assertNotXpathQuery("//th/a[text()='Herausgeber']");
        $this->assertNotXpathQuery("//th/a[text()='Ort']");
        $this->assertNotXpathQuery("//th/a[text()='Architektur']");
        $this->assertXpathQuery("//th/a[text()='Größe']");
        $this->assertXpathQuery("//tr[2]/td[3][@class='textright'][text()='\n42\xC2\xA0kB\n']");
    }

    public function testSoftwareActionComments()
    {
        $software1 = array(
            'Name' => 'name1',
            'Comment' => 'comment1',
            'Version' => 'version1',
            'Size' => 0,
        );
        $software2 = array(
            'Name' => 'name2',
            'Comment' => '',
            'Version' => 'version2',
            'Size' => 0,
        );
        $softwareModel = $this->getMock('Model_Software');
        $softwareModel->expects($this->any())
                      ->method('getArrayCopy')
                      ->will($this->onConsecutiveCalls($software1, $software2));
        $map = array(
            array('Windows', null),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->will($this->returnValue(array($softwareModel, $softwareModel)));
        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//tr[2]/td[1]/span[@title="comment1"]');
        $this->assertNotXpathQuery('//tr[3]/td[1]/span');
    }

    public function testSoftwareActionDuplicates()
    {
        $software1a = array(
            'Name' => 'name',
            'Comment' => '',
            'Version' => 'version1',
            'Size' => 0,
        );
        $software2 = array(
            'Name' => 'name',
            'Comment' => '',
            'Version' => 'version2',
            'Size' => 0,
        );
        $software1b = array(
            'Name' => 'name',
            'Comment' => '',
            'Version' => 'version1',
            'Size' => 0,
        );
        $softwareModel = $this->getMock('Model_Software');
        $softwareModel->expects($this->any())
                      ->method('getArrayCopy')
                      ->will($this->onConsecutiveCalls($software1a, $software2, $software1b));
        $map = array(
            array('Windows', null),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->will($this->returnValue(array($softwareModel, $softwareModel, $softwareModel)));
        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//tr[2]/td[1]/span[@class="duplicate"][text()="(2)"]');
        $this->assertNotXpathQuery('//tr[3]/td[1]/span');
    }

    public function testSoftwareHideBlacklisted()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('displayBlacklistedSoftware')
                      ->will($this->returnValue(false));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('Software', 'Name', 'asc', array('Status' => 'notIgnored'))
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/client/software/?id=1');
        $this->assertResponseStatusCode(200);
    }

    public function testSoftwareShowBlacklisted()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('displayBlacklistedSoftware')
                      ->will($this->returnValue(true));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('Software', 'Name', 'asc', array())
                        ->will($this->returnValue(array()));
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
        $this->_computer->expects($this->exactly(2))
                        ->method('getItems')
                        ->will($this->onConsecutiveCalls($products, array()));
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
        $this->_computer->expects($this->exactly(2))
                        ->method('getItems')
                        ->will($this->onConsecutiveCalls(array(), $products));
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
        $this->_computer->expects($this->exactly(2))
                        ->method('getItems')
                        ->will($this->onConsecutiveCalls($products, array()));
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
        $this->_computer->expects($this->exactly(2))
                        ->method('getItems')
                        ->will($this->onConsecutiveCalls($products, array()));
        $this->dispatch('/console/client/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//tr[2]/td[1]/span');
        $this->assertXpathQueryContentContains('//tr[3]/td[1]/span[@title="GUID: guid"]', 'name2');
    }

    public function testRegistryActionNoValues()
    {
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('RegistryData', 'Value.Name', 'asc')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/client/registry/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//table');
        $this->assertXpathQuery('//p/a[@href="/console/preferences/registryvalues/"]');
    }

    public function testRegistryActionWithValues()
    {
        $data = array(
            'Value' => array(
                'Name' => 'name',
                'FullPath' => 'full_path',
                'ValueInventoried' => 'value_inventoried',
            ),
            'Data' => 'data',
        );
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('RegistryData', 'Value.Name', 'asc')
                        ->will($this->returnValue(array($data)));
        $this->dispatch('/console/client/registry/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]/span[@title="full_path"]', "\nname\n");
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\nvalue_inventoried\n");
        $this->assertXpathQueryContentContains('//tr[2]/td[3]', "\ndata\n");
        $this->assertXpathQuery('//p/a[@href="/console/preferences/registryvalues/"]');
    }

    public function testVirtualmachinesActionNoMachines()
    {
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('VirtualMachine', 'Name', 'asc')
                        ->will($this->returnValue(array()));
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
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('VirtualMachine', 'Name', 'asc')
                        ->will($this->returnValue($vms));
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
        $map = array(
            array('AudioDevice', array($audiodevice)),
            array('InputDevice', array()),
            array('Port', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $map = array(
            array('AudioDevice', array()),
            array('InputDevice', $inputdevices),
            array('Port', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $map = array(
            array('AudioDevice', array()),
            array('InputDevice', array()),
            array('Port', array($port)),
            array('Windows', $this->getMock('Model\Client\WindowsInstallation')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $map = array(
            array('AudioDevice', array()),
            array('InputDevice', array()),
            array('Port', array($port)),
            array('Windows', null),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
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
        $customFields = $this->getMockBuilder('Model\Client\CustomFields')->disableOriginalConstructor()->getMock();
        $customFields->expects($this->once())
                     ->method('getArrayCopy')
                     ->will($this->returnValue(array()));
        $map = array(
            array('CustomFields', $customFields),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_getControllerPlugin('FlashMessenger')->addSuccessMessage('successMessage');
        $this->_disableTranslator();
        $this->dispatch('/console/client/customfields/?id=1');
        $this->assertXpathQueryContentContains(
            '//ul[@class="success"]/li',
            'successMessage'
        );
    }

    public function testCustomfieldsActionGet()
    {
        $data = array('field1' => 'value1', 'field2' => 'value2');
        $customFields = $this->getMockBuilder('Model\Client\CustomFields')->disableOriginalConstructor()->getMock();
        $customFields->expects($this->once())
                     ->method('getArrayCopy')
                     ->will($this->returnValue($data));
        $map = array(
            array('CustomFields', $customFields),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $form = $this->_formManager->get('Console\Form\CustomFields');
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
        $this->assertEmpty($this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
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
        $form = $this->_formManager->get('Console\Form\CustomFields');
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
        $this->assertEmpty($this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
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
        $form = $this->_formManager->get('Console\Form\CustomFields');
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('setUserDefinedInfo')
                        ->with($postData['Fields']);
        $this->dispatch('/console/client/customfields/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/customfields/?id=1');
        $this->assertContains(
            'The information was successfully updated.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testPackagesActionNoPackages()
    {
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->never())
             ->method('setPackages');
        $form->expects($this->never())
             ->method('render');
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('PackageAssignment', 'Name', 'asc')
                        ->will($this->returnValue(array()));
        $this->_computer->expects($this->once())
                        ->method('getInstallablePackages')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//h2');
        $this->assertNotXpathQuery('//table');
    }

    public function testPackagesActionAssigned()
    {
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->never())
             ->method('setPackages');
        $form->expects($this->never())
             ->method('render');
        $assignments = array(
            array(
                'Computer' => 1,
                'Name' => 'package1',
                'Status' => null,
                'Timestamp' => 'timestamp1',
            ),
            array(
                'Computer' => 1,
                'Name' => 'package2',
                'Status' => 'NOTIFIED',
                'Timestamp' => 'timestamp2',
            ),
            array(
                'Computer' => 1,
                'Name' => 'package3',
                'Status' => 'SUCCESS',
                'Timestamp' => 'timestamp3',
            ),
            array(
                'Computer' => 1,
                'Name' => 'package4',
                'Status' => '<ERROR>',
                'Timestamp' => 'timestamp4',
            ),
        );
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('PackageAssignment', 'Name', 'asc')
                        ->will($this->returnValue($assignments));
        $this->_computer->expects($this->once())
                        ->method('getInstallablePackages')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nAssigned packages\n");
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertXpathQueryContentContains(
            '//tr[2]/td[2]/span[@class="package_notnotified"]',
            'nicht benachrichtigt'
        );
        $this->assertXpathQueryContentContains('//tr[3]/td[2]/span[@class="package_inprogress"]', 'läuft');
        $this->assertXpathQueryContentContains('//tr[4]/td[2]/span[@class="package_success"]', 'installiert');
        $this->assertXpathQueryContentContains('//tr[5]/td[2]/span[@class="package_error"]', '<ERROR>');
        $this->assertXpathQueryContentContains(
            '//tr[3]/td[4]/a[@href="/console/client/removepackage/?id=1&package=package2"]',
            'entfernen'
        );
    }

    public function testPackagesActionInstallable()
    {
        $packages = array('package1', 'package2');
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->once())
             ->method('setPackages')
             ->with($packages);
        $form->expects($this->once())
             ->method('setAttribute')
             ->with('action', '/console/client/installpackage/?id=1');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('PackageAssignment', 'Name', 'asc')
                        ->will($this->returnValue(array()));
        $this->_computer->expects($this->once())
                        ->method('getInstallablePackages')
                        ->will($this->returnValue($packages));
        $this->dispatch('/console/client/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nPakete installieren\n");
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertXPathQuery('//form');
    }

    public function testGroupsActionNoGroups()
    {
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize(array());
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
        $form->expects($this->never())
             ->method('render');
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_ALL)
                        ->will($this->returnValue(array()));
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
            array('Name' => 'group1'),
            array('Name' => 'group2'),
        );
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize($groups);
        $membership = array(
            'GroupName' => 'group1',
            'Membership' => \Model_GroupMembership::TYPE_EXCLUDED
        );
        $formGroups = array(
            'group1' => \Model_GroupMembership::TYPE_EXCLUDED,
            'group2' => \Model_GroupMembership::TYPE_DYNAMIC,
        );
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $form->expects($this->once())
             ->method('setData')
             ->with(array('Groups' => $formGroups));
        $form->expects($this->once())
             ->method('setAttribute')
             ->with('action', '/console/client/managegroups/?id=1');
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_ALL)
                        ->will($this->returnValue(array($membership)));
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap(array(array('Id', 1))));
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
            array('Name' => 'group1'),
            array('Name' => 'group2'),
        );
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize($groups);
        $memberships = array(
            array(
                'GroupName' => 'group1',
                'Membership' => \Model_GroupMembership::TYPE_DYNAMIC,
            ),
            array(
                'GroupName' => 'group2',
                'Membership' => \Model_GroupMembership::TYPE_STATIC,
            ),
        );
        $formGroups = array(
            'group1' => \Model_GroupMembership::TYPE_DYNAMIC,
            'group2' => \Model_GroupMembership::TYPE_STATIC,
        );
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $form->expects($this->once())
             ->method('setData')
             ->with(array('Groups' => $formGroups));
        $form->expects($this->once())
             ->method('setAttribute')
             ->with('action', '/console/client/managegroups/?id=1');
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_ALL)
                        ->will($this->returnValue($memberships));
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap(array(array('Id', 1))));
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
        $this->_computer->expects($this->once())
                        ->method('getAllConfig')
                        ->will($this->returnValue($config));
        $form = $this->_formManager->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_computer);
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
        $this->dispatch('/console/client/configuration/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->_formManager->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_computer);
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
        $this->dispatch('/console/client/configuration/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form');
    }

    public function testConfigurationActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->_formManager->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setClientObject')
             ->with($this->_computer);
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
        $this->dispatch('/console/client/configuration/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/configuration/?id=1');
    }

    public function testDeleteActionGet()
    {
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $map = array(
            array('Name', 'name'),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('delete');
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
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('delete');
        $this->dispatch('/console/client/delete/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/client/general/?id=1');
    }

    public function testDeleteActionPostYesDeleteInterfacesSuccess()
    {
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Name', 'name'),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('delete')
                        ->with(false, true)
                        ->will($this->returnValue(true));
        $postData = array('yes' => 'Yes', 'DeleteInterfaces' => '1');
        $this->dispatch('/console/client/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $this->assertContains(
            array("Client '%s' was successfully deleted." => 'name'),
            $flashMessenger->getCurrentSuccessMessages()
        );
        $this->assertEmpty($flashMessenger->getCurrentErrorMessages());
    }

    public function testDeleteActionPostYesKeepInterfacesError()
    {
        $form = $this->_formManager->get('Console\Form\DeleteClient');
        $form->expects($this->never())
             ->method('render');
        $map = array(
            array('Name', 'name'),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('delete')
                        ->with(false, false)
                        ->will($this->returnValue(false));
        $postData = array('yes' => 'Yes', 'DeleteInterfaces' => '0');
        $this->dispatch('/console/client/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $this->assertEmpty($flashMessenger->getCurrentSuccessMessages());
        $this->assertContains(
            array("Client '%s' could not be deleted." => 'name'),
            $flashMessenger->getCurrentErrorMessages()
        );
    }

    public function testRemovepackageActionGet()
    {
        $this->_computer->expects($this->never())
                        ->method('unaffectPackage');
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('unaffectPackage');
        $this->dispatch('/console/client/removepackage/?id=1&package=name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testRemovepackageActionPostYes()
    {
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('unaffectPackage')
                        ->with('name');
        $this->dispatch('/console/client/removepackage/?id=1&package=name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testInstallpackageActionGet()
    {
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('getData');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('installPackage');
        $this->dispatch('/console/client/installpackage/?id=1');
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testInstallpackageActionPostInvalid()
    {
        $postData = array('package1' => '1', 'package2' => '1');
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(false));
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->never())
             ->method('getData');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('installPackage');
        $this->dispatch('/console/client/installpackage/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testInstallpackageActionPostValid()
    {
        $postData = array('Packages' => array('package1' => '0', 'package2' => '1'));
        $form = $this->_formManager->get('Console\Form\Package\Assign');
        $form->expects($this->once())
             ->method('isValid')
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('setData')
             ->with($postData);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($postData));
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('installPackage')
                        ->with('package2');
        $this->dispatch('/console/client/installpackage/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/packages/?id=1');
    }

    public function testManagegroupsActionGet()
    {
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->never())
             ->method('isValid');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                            ->method('setGroups');
        $this->dispatch('/console/client/managegroups/?id=1');
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testManagegroupsActionPostInvalid()
    {
        $postData = array(
            'Groups' => array('group1' => '1', 'group2' => '2')
        );
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('setGroupsByName');
        $this->dispatch('/console/client/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testManagegroupsActionPostValid()
    {
        $postData = array(
            'Groups' => array('group1' => '1', 'group2' => '2')
        );
        $form = $this->_formManager->get('Console\Form\GroupMemberships');
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
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('setGroupsByName')
                        ->with($postData['Groups']);
        $this->dispatch('/console/client/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/client/groups/?id=1');
    }

    public function testSearchActionNoPreset()
    {
        $form = $this->_formManager->get('Console\Form\Search');
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
        $form->expects($this->once())
             ->method('render');
        $this->dispatch('/console/client/search/');
        $this->assertResponseStatusCode(200);
    }

    public function testSearchActionPreset()
    {
        $form = $this->_formManager->get('Console\Form\Search');
        $form->expects($this->once())
             ->method('setData')
             ->with(array('filter' => 'Name', 'search' => 'value'));
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
        $form->expects($this->once())
             ->method('render');
        $this->dispatch('/console/client/search/?filter=Name&search=value');
        $this->assertResponseStatusCode(200);
    }

    public function testImportActionGet()
    {
        $form = $this->_formManager->get('Console\Form\Import');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('setData');
        $form->expects($this->never())
             ->method('getData');
        $form->expects($this->once())
             ->method('render')
             ->will($this->returnValue('<form></form>'));
        $this->_inventoryUploader->expects($this->never())
                                 ->method('uploadFile');
        $this->dispatch('/console/client/import/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="error"]');
        $this->assertXpathQueryContentContains('//h1', "\nImport lokal erzeugter Inventardaten\n");
        $this->assertXPathQuery('//form');
    }

    public function testImportActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->_formManager->get('Console\Form\Import');
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
        $this->_inventoryUploader->expects($this->never())
                                 ->method('uploadFile');
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
        $form = $this->_formManager->get('Console\Form\Import');
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
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('communicationServerUri')
                      ->will($this->returnValue('http://example.net/server'));
        $response = new \Zend\Http\Response;
        $response->setStatusCode(500)
                 ->setReasonPhrase('reason_phrase');
        $this->_inventoryUploader->expects($this->once())
                                 ->method('uploadFile')
                                 ->with('uploaded_file')
                                 ->will($this->returnValue($response));
        $this->dispatch('/console/client/import/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="error"]',
            "\nFehler beim Hochladen. Server http://example.net/server antwortete mit Fehler 500: reason_phrase\n"
        );
        $this->assertXpathQueryContentContains('//h1', "\nImport lokal erzeugter Inventardaten\n");
    }

    public function testImportActionPostValidSuccess()
    {
        $fileSpec = array('tmp_name' => 'uploaded_file');
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $postData = array('key' => 'value');
        $form = $this->_formManager->get('Console\Form\Import');
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
        $response = new \Zend\Http\Response;
        $response->setStatusCode(200);
        $this->_inventoryUploader->expects($this->once())
                                 ->method('uploadFile')
                                 ->with('uploaded_file')
                                 ->will($this->returnValue($response));
        $this->dispatch('/console/client/import/', 'POST', $postData);
        $this->assertRedirectTo('/console/client/index/');
    }

    public function testExportAction()
    {
        $xmlContent = "xml_content\n";
        $document = $this->getMock('Model_DomDocument_InventoryRequest');
        $document->expects($this->once())
                 ->method('getFilename')
                 ->will($this->returnValue('filename.xml'));
        $document->expects($this->once())
                 ->method('saveXml')
                 ->will($this->returnValue($xmlContent));
        $this->_computer->expects($this->once())
                        ->method('toDomDocument')
                        ->will($this->returnValue($document));

        $this->dispatch('/console/client/export/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset=utf-8');
        $this->assertResponseHeaderContains('Content-Disposition', 'attachment; filename="filename.xml"');
        $this->assertResponseHeaderContains('Content-Length', strlen($xmlContent));
        $this->assertEquals($xmlContent, $this->getResponse()->getContent());
    }
}
