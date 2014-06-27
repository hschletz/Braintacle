<?php
/**
 * Tests for ComputerController
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
 * Tests for ComputerController
 */
class ComputerControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Computer mock
     * @var \Model_Computer
     */
    protected $_computer;

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
     * Sample computer data
     * @var array[]
     */
    protected $_sampleComputers = array(
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
            'UserDefinedInfo.customField' => 'custom1',
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
            'UserDefinedInfo.customField' => 'custom2',
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

        $legacyFormManager = new \Zend\ServiceManager\ServiceManager;
        $legacyFormManager->setService('Console\Form\CustomFields', $this->getMock('Form_UserDefinedInfo'));
        $legacyFormManager->setService('Console\Form\AssignPackages', $this->getMock('Form_AffectPackages'));
        $legacyFormManager->setService('Console\Form\GroupMemberships', $this->getMock('Form_ManageGroupMemberships'));
        $legacyFormManager->setService('Console\Form\ClientConfig', $this->getMock('Form_Configuration'));

        $this->_formManager = new \Zend\Form\FormElementManager;
        $this->_formManager->setServiceLocator($legacyFormManager);
        $this->_formManager->setService('Console\Form\DeleteComputer', $this->getMock('Console\Form\DeleteComputer'));
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
        return new \Console\Controller\ComputerController(
            $this->_computer,
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

    public function testInvalidComputer()
    {
        $this->_computer->expects($this->once())
                        ->method('fetchById')
                        ->with(42)
                        ->will($this->throwException(new \RuntimeException));
        $this->dispatch('/console/computer/general/?id=42');
        $this->assertRedirectTo('/console/computer/index/');
        $this->assertContains(
            'The requested computer does not exist.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    public function testMenuForWindowsComputers()
    {
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will(
                            $this->returnValueMap(
                                array(
                                    array('Windows', $this->getMock('Model_Windows')),
                                )
                            )
                        );
        $this->dispatch('/console/computer/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertXpathQuery($query . '/a[@href="/console/computer/windows/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/computer/msoffice/?id=1"]');
        $this->assertXpathQuery($query . '/a[@href="/console/computer/registry/?id=1"]');
    }

    public function testMenuForNonWindowsComputers()
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
        $this->dispatch('/console/computer/printers/?id=1');
        $query = '//ul[contains(concat(" ", normalize-space(@class), " "), " navigation_details ")]/li';
        $this->assertNotXpathQuery($query . '/a[@href="/console/computer/windows/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/computer/msoffice/?id=1"]');
        $this->assertNotXpathQuery($query . '/a[@href="/console/computer/registry/?id=1"]');
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//p[@class="textcenter"]', "\nNumber of computers: 2\n");
        $this->assertXpathQueryCount('//th', 7);
        $this->assertXpathQueryCount('//th[@class="textright"]', 2); // CpuClock and PhysicalMemory
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/computer/general/?id=2"]',
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/?columns=Name,InventoryDate');
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/?jumpto=software');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/computer/software/?id=2"]',
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/?jumpto=invalid');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/computer/general/?id=2"]',
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/?filter=PackageError&search=packageName');
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch(
            '/console/computer/index/?' .
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
                        ->will($this->returnValue($this->_sampleComputers));
        $this->dispatch('/console/computer/index/?filter=Software&search=%C2%99');
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
                        ->will($this->returnValue($this->_sampleComputers));
        $query = 'filter=Name&search=test&operator=eq&invert=1';
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            '//p[@class="textcenter"][contains(text(), "2 matches")]'
        );
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/computer/search/?$query'][text()='Edit filter']"
        );
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/group/add/?$query'][text()='Save to group']"
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
                        ->will($this->returnValue($this->_sampleComputers));
        $query = 'filter=InventoryDate&search=2014-05-12&operator=eq&invert=1';
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery(
            "//p[@class='textcenter']/a[@href='/console/group/add/?$query'][text()='Save to group']"
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
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
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
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
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
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
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
                        ->will($this->returnValue($this->_sampleComputers));
        $query = 'filter=Registry.value&search=test&operator=like&invert=0';
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "value");
    }

    public function testIndexActionWithCustomSearchOnCustomField()
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
                        ->will($this->returnValue($this->_sampleComputers));
        $query = 'filter=UserDefinedInfo.customField&search=test&operator=like&invert=0';
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "customField");
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
                        ->will($this->returnValue($this->_sampleComputers));
        $query = 'filter=UserDefinedInfo.TAG&search=test&operator=like&invert=0';
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//th/a', "Category");
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
        $this->dispatch("/console/computer/index/?customSearch=button&$query");
        $this->assertRedirectTo("/console/computer/search/?customSearch=button&$query");
    }

    public function testIndexActionMessages()
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addErrorMessage('error');
        $flashMessenger->addSuccessMessage(array('success %d' => 42));
        $this->_computer->expects($this->once())
                        ->method('fetch')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/computer/index/');
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
        $this->dispatch('/console/computer/general/?id=1');
        $this->assertResponseStatusCode(200);

        $query = "//dl/dt[text()='\n%s\n']/following::dd[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'ID', 1));
        $this->assertXPathQuery(sprintf($query, 'Client ID', 'client_id'));
        $this->assertXPathQuery(sprintf($query, 'Inventory date', '29.05.2014 11:16:15'));
        $this->assertXPathQuery(sprintf($query, 'Last contact', '29.05.2014 11:17:34'));
        $this->assertXPathQuery(sprintf($query, 'User Agent', 'user_agent'));
        $this->assertXPathQuery(sprintf($query, 'Model', 'manufacturer model'));
        $this->assertXPathQuery(sprintf($query, 'Serial number', 'serial'));
        $this->assertXPathQuery(sprintf($query, 'Asset tag', 'asset_tag'));
        $this->assertXPathQuery(sprintf($query, 'Type', 'type'));
        $this->assertXPathQuery(sprintf($query, 'Operating System', 'os_name os_version_string (os_version_number)'));
        $this->assertXPathQuery(sprintf($query, 'Comment', 'os_comment'));
        $this->assertXPathQuery(sprintf($query, 'CPU type', 'cpu_type'));
        $this->assertXPathQuery(sprintf($query, 'CPU clock', "1234\xC2\xA0MHz"));
        $this->assertXPathQuery(sprintf($query, 'Number of CPU cores', 2));
        $this->assertXPathQuery(sprintf($query, 'RAM detected by agent', "5\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'RAM reported by OS', "1234\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'Swap memory', "5678\xC2\xA0MB"));
        $this->assertXPathQuery(sprintf($query, 'Last user logged in', 'user_name'));
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
        $this->dispatch('/console/computer/general/?id=1');
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
        $this->dispatch('/console/computer/general/?id=1');
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
        $this->dispatch('/console/computer/general/?id=1');
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
        $map = array(
            array('Company', 'company'),
            array('Owner', 'owner'),
            array('ProductId', 'product_id'),
            array('ProductKey', 'product_key'),
            array('ManualProductKey', 'manual_product_key'),
        );
        $windows = $this->getMock('Model_Windows');
        $windows->expects($this->any())
                ->method('offsetGet')
                ->will($this->returnValueMap($map));
        $windows->expects($this->never())
                ->method('offsetSet');
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap(array(array('Windows', $windows))));
        $this->dispatch('/console/computer/windows/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQuery('//form[@action=""][@method="POST"]');
        $query = '//td[@class="label"][text()="%s"]/following::td[1][text()="%s"]';
        $this->assertXPathQuery(sprintf($query, 'Company', 'company'));
        $this->assertXPathQuery(sprintf($query, 'Owner', 'owner'));
        $this->assertXPathQuery(sprintf($query, 'Product ID', 'product_id'));
        $this->assertXPathQuery(sprintf($query, 'Product key (reported by agent)', 'product_key'));
        $this->assertXpathQueryContentContains('//tr[5]/td[1]', $form->get('Key')->getLabel());
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
        $windows = $this->getMock('Model_Windows');
        $windows->expects($this->never())
                ->method('offsetSet');
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap(array(array('Windows', $windows))));
        $this->dispatch('/console/computer/windows/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//*[@class="error"]//*[text()="message"]');
    }

    public function testWindowsActionPostValid()
    {
        $postData = array('Key' => 'entered_key');
        $windows = $this->getMock('Model_Windows');
        $windows->expects($this->once())
                ->method('offsetSet')
                ->with('ManualProductKey', 'entered_key');
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
            array('Windows', $windows),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/computer/windows/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/windows/?id=1');
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
        $this->dispatch('/console/computer/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nGlobal network configuration\n']");
        $query = "//td[text()='\n%s\n']/following::td[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'DNS server', 'dns_server'));
        $this->assertXPathQuery(sprintf($query, 'Default gateway', 'default_gateway'));
        $this->assertNotXpathQuery("//h2[text()='\nNetwork interfaces\n']");
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
        $this->dispatch('/console/computer/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nGlobal network configuration\n']");
        $this->assertXpathQuery("//h2[text()='\nNetwork interfaces\n']");
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
        $this->dispatch('/console/computer/network/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nGlobal network configuration\n']");
        $this->assertNotXpathQuery("//h2[text()='\nNetwork interfaces\n']");
        $this->assertXpathQuery("//h2[text()='\nModems\n']");
        $this->assertXpathQueryCount('//td', 2);
    }

    public function testStorageActionWindows()
    {
        $devices = array(
            array(
                'Type' => 'type',
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
            array('Windows', $this->getMock('Model_Windows')),
            array('StorageDevice', $devices),
            array('Volume', $filesystems),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/computer/storage/?id=1');
        $this->assertResponseStatusCode(200);
        // Devices
        $this->assertXpathQueryCount('//table[1]//th', 3);
        $this->assertXpathQueryContentContains('//table[1]/tr[2]/td[3]', "\n1,0 GB\n");
        // Filesystem 1
        $this->assertXpathQuery("//table[2]//th[text()='\nLetter\n']");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[5]', "\n9,8 GB\n");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[6]', "\n5,9 GB (60%)\n");
        $this->assertXpathQueryContentContains('//table[2]/tr[2]/td[7]', "\n3,9 GB (40%)\n");
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
        $this->dispatch('/console/computer/storage/?id=1');
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
        $this->dispatch('/console/computer/display/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nDisplay controllers\n']");
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
        $this->dispatch('/console/computer/display/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nDisplays\n']");
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
        $this->dispatch('/console/computer/bios/?id=1');
        $this->assertResponseStatusCode(200);
        $query = "//dl/dt[text()='\n%s\n']/following::dd[1][text()='\n%s\n']";
        $this->assertXPathQuery(sprintf($query, 'Manufacturer', 'manufacturer'));
        $this->assertXPathQuery(sprintf($query, 'Date', 'date'));
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
        $this->dispatch('/console/computer/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nMemory slots\n']");
        $this->assertNotXpathQuery("//h2[text()='\nExtension slots\n']");
        $this->assertXpathQuery("//h2[text()='\nControllers\n']");
        $this->assertNotXpathQuery("//th[text()='\nDriver version\n']");
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
            array('Windows', $this->getMock('Model_Windows')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/computer/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th[text()='\nDriver version\n']");
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
        $this->dispatch('/console/computer/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nMemory slots\n']");
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
                'Type' => 'type',
                'Description' => 'description',
                'Status' => 'status',
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
        $this->dispatch('/console/computer/system/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nExtension slots\n']");
        $this->assertXpathQueryCount('//tr', 2);
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
        $this->dispatch('/console/computer/printers/?id=1');
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
            array('Windows', $this->getMock('Model_Windows')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->will($this->returnValue(array($softwareModel, $softwareModel)));
        $this->dispatch('/console/computer/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[text()='Version']");
        $this->assertXpathQuery("//th/a[text()='Publisher']");
        $this->assertXpathQuery("//th/a[text()='Location']");
        $this->assertXpathQuery("//th/a[text()='Architecture']");
        $this->assertNotXpathQuery("//th/a[text()='Size']");
        $this->assertXpathQuery("//tr[2]/td[5][text()='\n32 Bit\n']");
        $this->assertXpathQuery("//tr[3]/td[5][text()='\n\n']");
    }

    public function testSoftwareActionUnix()
    {
        $software1 = array(
            'Name' => 'name1',
            'Comment' => '',
            'Version' => 'version1',
            'Size' => 42 * 1024,
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
        $this->dispatch('/console/computer/software/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//th/a[text()='Version']");
        $this->assertNotXpathQuery("//th/a[text()='Publisher']");
        $this->assertNotXpathQuery("//th/a[text()='Location']");
        $this->assertNotXpathQuery("//th/a[text()='Architecture']");
        $this->assertXpathQuery("//th/a[text()='Size']");
        $this->assertXpathQuery("//tr[2]/td[3][text()='\n42 kB\n']");
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
        $this->dispatch('/console/computer/software/?id=1');
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
        $this->dispatch('/console/computer/software/?id=1');
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
        $this->dispatch('/console/computer/software/?id=1');
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
        $this->dispatch('/console/computer/software/?id=1');
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
        $this->dispatch('/console/computer/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nInstalled Microsoft Office products\n']");
        $this->assertNotXpathQuery("//h2[text()='\nUnused Microsoft Office licenses\n']");
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
        $this->dispatch('/console/computer/msoffice/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nInstalled Microsoft Office products\n']");
        $this->assertXpathQuery("//h2[text()='\nUnused Microsoft Office licenses\n']");
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
        $this->dispatch('/console/computer/msoffice/?id=1');
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
        $this->dispatch('/console/computer/msoffice/?id=1');
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
        $this->dispatch('/console/computer/registry/?id=1');
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
        $this->dispatch('/console/computer/registry/?id=1');
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
        $this->dispatch('/console/computer/virtualmachines/?id=1');
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
        $this->dispatch('/console/computer/virtualmachines/?id=1');
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
        $this->dispatch('/console/computer/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery("//h2[text()='\nAudio devices\n']");
        $this->assertNotXpathQuery("//h2[text()='\nInput devices\n']");
        $this->assertNotXpathQuery("//h2[text()='\nPorts\n']");
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
        $this->dispatch('/console/computer/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudio devices\n']");
        $this->assertXpathQuery("//h2[text()='\nInput devices\n']");
        $this->assertNotXpathQuery("//h2[text()='\nPorts\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryContentContains('//tr[2]/td[1]', "\ntype\n");
        // TODO: test that "Keyboard" is actually a translated string
        $this->assertXpathQueryContentContains('//tr[3]/td[1]', "\nKeyboard\n");
        $this->assertXpathQueryContentContains('//tr[4]/td[1]', "\nPointing device\n");
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
            array('Windows', $this->getMock('Model_Windows')),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/computer/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudio devices\n']");
        $this->assertNotXpathQuery("//h2[text()='\nInput devices\n']");
        $this->assertXpathQuery("//h2[text()='\nPorts\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryCount('//tr', 2);
        $this->assertNotXpathQueryContentContains('//th', "\nConnector\n");
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
        $this->dispatch('/console/computer/misc/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery("//h2[text()='\nAudio devices\n']");
        $this->assertNotXpathQuery("//h2[text()='\nInput devices\n']");
        $this->assertXpathQuery("//h2[text()='\nPorts\n']");
        $this->assertXpathQueryCount('//table', 1);
        $this->assertXpathQueryCount('//tr', 2);
        $this->assertXpathQueryContentContains('//th', "\nConnector\n");
    }

    public function testCustomfieldsActionFlashMessage()
    {
        $map = array(
            array('CustomFields', array()),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_getControllerPlugin('FlashMessenger')->addSuccessMessage('successMessage');
        $this->dispatch('/console/computer/customfields/?id=1');
        $this->assertXpathQueryContentContains(
            '//ul[@class="success"]/li',
            'successMessage'
        );
    }

    public function testCustomfieldsActionGet()
    {
        $customFields = array(
            'field1' => 'value1',
            'field2' => 'value2',
        );
        $map = array(
            array('CustomFields', $customFields),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\CustomFields');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('getValues');
        $form->expects($this->exactly(2))
             ->method('setDefault')
             ->with(
                 $this->callback(
                     function($name) use($customFields) {
                        return isset($customFields[$name]);
                     }
                 ),
                 $this->callback(
                     function($value) use($customFields) {
                        return in_array($value, $customFields);
                     }
                 )
             );
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->dispatch('/console/computer/customfields/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertEmpty($this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
        $this->assertXpathQueryContentContains(
            '//p/a[@href="/console/preferences/customfields/"]',
            'Define fields'
        );
    }

    public function testCustomfieldsPostInvalid()
    {
        $postData = array(
            'field1' => 'value1',
            'field2' => 'value2',
        );
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\CustomFields');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('getValues');
        $form->expects($this->never())
             ->method('setDefault');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->dispatch('/console/computer/customfields/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertEmpty($this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages());
        $this->assertXpathQueryContentContains(
            '//p/a[@href="/console/preferences/customfields/"]',
            'Define fields'
        );
    }

    public function testCustomfieldsPostValid()
    {
        $postData = array(
            'field1' => 'value1',
            'field2' => 'value2',
        );
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\CustomFields');
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getValues')
             ->will($this->returnValue($postData));
        $form->expects($this->never())
             ->method('setDefault');
        $form->expects($this->never())
             ->method('__toString');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('setUserDefinedInfo')
                        ->with($postData);
        $this->dispatch('/console/computer/customfields/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/customfields/?id=1');
        $this->assertContains(
            'The information was successfully updated.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testPackagesActionNoPackages()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->once())
             ->method('addPackages')
             ->with($this->_computer)
             ->will($this->returnValue(0));
        $form->expects($this->never())
             ->method('__toString');
        $this->_computer->expects($this->once())
                        ->method('getItems')
                        ->with('PackageAssignment', 'Name', 'asc')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/computer/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//h2');
        $this->assertNotXpathQuery('//table');
    }

    public function testPackagesActionAssigned()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->once())
             ->method('addPackages')
             ->with($this->_computer)
             ->will($this->returnValue(0));
        $form->expects($this->never())
             ->method('__toString');
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
        $this->dispatch('/console/computer/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nAssigned packages\n");
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertXpathQueryContentContains('//tr[2]/td[2]/span[@class="package_notnotified"]', 'not notified');
        $this->assertXpathQueryContentContains('//tr[3]/td[2]/span[@class="package_inprogress"]', 'in progress');
        $this->assertXpathQueryContentContains('//tr[4]/td[2]/span[@class="package_success"]', 'installed');
        $this->assertXpathQueryContentContains('//tr[5]/td[2]/span[@class="package_error"]', '<ERROR>');
        $this->assertXpathQueryContentContains(
            '//tr[3]/td[4]/a[@href="/console/computer/removepackage/?id=1&package=package2"]',
            'remove'
        );
    }

    public function testPackagesActionInstallable()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->once())
             ->method('addPackages')
             ->with($this->_computer)
             ->will($this->returnValue(1));
        $form->expects($this->once())
             ->method('setAction')
             ->with('/console/computer/installpackage/?id=1');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
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
        $this->dispatch('/console/computer/packages/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nInstall packages\n");
        $this->assertXpathQueryCount('//h2', 1);
    }

    public function testGroupsActionNoGroups()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('addGroups')
             ->with($this->_computer)
             ->will($this->returnValue(0));
        $form->expects($this->never())
             ->method('__toString');
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_INCLUDED, 'GroupName', 'asc')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/computer/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//h2');
        $this->assertNotXpathQuery('//table');
    }

    public function testGroupsActionMember()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('addGroups')
             ->with($this->_computer)
             ->will($this->returnValue(0));
        $form->expects($this->never())
             ->method('__toString');
        $memberships = array(
            array(
                'GroupName' => 'group_name',
                'GroupId' => 42,
                'Membership' => \Model_GroupMembership::TYPE_DYNAMIC,
            ),
        );
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_INCLUDED, 'GroupName', 'asc')
                        ->will($this->returnValue($memberships));
        $this->dispatch('/console/computer/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nGroup memberships\n");
        $this->assertXpathQueryCount('//h2', 1);
        $this->assertXpathQueryContentContains(
            '//tr[2]/td[1]/a[@href="/console/group/general/?id=42"]',
            'group_name'
        );
        $this->assertXpathQueryContentContains('//tr[2]/td[2]', "\nautomatic\n");
    }

    public function testGroupsActionManage()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('addGroups')
             ->with($this->_computer)
             ->will($this->returnValue(1));
        $form->expects($this->once())
             ->method('setAction')
             ->with('/console/computer/managegroups/?id=1');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('getGroups')
                        ->with(\Model_GroupMembership::TYPE_INCLUDED, 'GroupName', 'asc')
                        ->will($this->returnValue(array()));
        $this->dispatch('/console/computer/groups/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains('//h2', "\nManage memberships\n");
        $this->assertXpathQueryCount('//h2', 1);
    }

    public function testConfigurationActionGet()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setObject')
             ->with($this->_computer);
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->dispatch('/console/computer/configuration/?id=1');
        $this->assertResponseStatusCode(200);
    }

    public function testConfigurationActionPostInvalid()
    {
        $postData = array('key' => 'value');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setObject')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('process');
        $form->expects($this->once())
             ->method('__toString')
             ->will($this->returnValue(''));
        $this->dispatch('/console/computer/configuration/?id=1', 'POST', $postData);
        $this->assertResponseStatusCode(200);
    }

    public function testConfigurationActionPostValid()
    {
        $postData = array('key' => 'value');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\ClientConfig');
        $form->expects($this->once())
             ->method('setObject')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('process');
        $form->expects($this->never())
             ->method('__toString');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->dispatch('/console/computer/configuration/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/configuration/?id=1');
    }

    public function testDeleteActionGet()
    {
        $form = $this->_formManager->get('Console\Form\DeleteComputer');
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
        $this->dispatch('/console/computer/delete/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="textcenter"]',
            "\nComputer 'name' will be permanently deleted. Continue?\n"
        );
        $this->assertXPathQuery('//form');
    }

    public function testDeleteActionPostNo()
    {
        $form = $this->_formManager->get('Console\Form\DeleteComputer');
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
        $this->dispatch('/console/computer/delete/?id=1', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/computer/general/?id=1');
    }

    public function testDeleteActionPostYesDeleteInterfacesSuccess()
    {
        $form = $this->_formManager->get('Console\Form\DeleteComputer');
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
        $this->dispatch('/console/computer/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/index/');
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $this->assertContains(
            array("Computer '%s' was successfully deleted." => 'name'),
            $flashMessenger->getCurrentSuccessMessages()
        );
        $this->assertEmpty($flashMessenger->getCurrentErrorMessages());
    }

    public function testDeleteActionPostYesKeepInterfacesError()
    {
        $form = $this->_formManager->get('Console\Form\DeleteComputer');
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
        $this->dispatch('/console/computer/delete/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/index/');
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $this->assertEmpty($flashMessenger->getCurrentSuccessMessages());
        $this->assertContains(
            array("Computer '%s' could not be deleted." => 'name'),
            $flashMessenger->getCurrentErrorMessages()
        );
    }

    public function testRemovepackageActionGet()
    {
        $this->_computer->expects($this->never())
                        ->method('unaffectPackage');
        $this->dispatch('/console/computer/removepackage/?id=1&package=name');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p',
            'Package "name" will no longer be assigned to this computer. Continue?'
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
        $this->dispatch('/console/computer/removepackage/?id=1&package=name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/computer/packages/?id=1');
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
        $this->dispatch('/console/computer/removepackage/?id=1&package=name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/computer/packages/?id=1');
    }

    public function testInstallpackageActionGet()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->never())
             ->method('addPackages');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('getValues');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('installPackage');
        $this->dispatch('/console/computer/installpackage/?id=1');
        $this->assertRedirectTo('/console/computer/packages/?id=1');
    }

    public function testInstallpackageActionPostInvalid()
    {
        $postData = array('package1' => '1', 'package2' => '1');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->once())
             ->method('addPackages')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('getValues');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('installPackage');
        $this->dispatch('/console/computer/installpackage/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/packages/?id=1');
    }

    public function testInstallpackageActionPostValid()
    {
        $postData = array('package1' => '1', 'package2' => '1');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\AssignPackages');
        $form->expects($this->once())
             ->method('addPackages')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getValues')
             ->will($this->returnValue($postData));
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->exactly(2))
                        ->method('installPackage')
                        ->with(
                            $this->callback(
                                function($name) use($postData) {
                                    return isset($postData[$name]);
                                }
                            )
                        );
        $this->dispatch('/console/computer/installpackage/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/packages/?id=1');
    }

    public function testManagegroupsActionGet()
    {
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->never())
             ->method('addGroups');
        $form->expects($this->never())
             ->method('isValid');
        $form->expects($this->never())
             ->method('getValues');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                            ->method('setGroups');
        $this->dispatch('/console/computer/managegroups/?id=1');
        $this->assertRedirectTo('/console/computer/groups/?id=1');
    }

    public function testManagegroupsActionPostInvalid()
    {
        $postData = array('group1' => '1', 'group2' => '2');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('addGroups')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(false));
        $form->expects($this->never())
             ->method('getValues');
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->never())
                        ->method('setGroups');
        $this->dispatch('/console/computer/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/groups/?id=1');
    }

    public function testManagegroupsActionPostValid()
    {
        $postData = array('group1' => '1', 'group2' => '2');
        $form = $this->_formManager->getServiceLocator()->get('Console\Form\GroupMemberships');
        $form->expects($this->once())
             ->method('addGroups')
             ->with($this->_computer);
        $form->expects($this->once())
             ->method('isValid')
             ->with($postData)
             ->will($this->returnValue(true));
        $form->expects($this->once())
             ->method('getValues')
             ->will($this->returnValue($postData));
        $map = array(
            array('Id', 1),
        );
        $this->_computer->expects($this->any())
                        ->method('offsetGet')
                        ->will($this->returnValueMap($map));
        $this->_computer->expects($this->once())
                        ->method('setGroups')
                        ->with($postData);
        $this->dispatch('/console/computer/managegroups/?id=1', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/groups/?id=1');
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
                 $this->matchesRegularExpression('#^(GET|/console/computer/index/)$#')
             );
        $form->expects($this->once())
             ->method('render');
        $this->dispatch('/console/computer/search/');
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
                 $this->matchesRegularExpression('#^(GET|/console/computer/index/)$#')
             );
        $form->expects($this->once())
             ->method('render');
        $this->dispatch('/console/computer/search/?filter=Name&search=value');
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
        $this->dispatch('/console/computer/import/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="error"]');
        $this->assertXpathQueryContentContains('//h1', "\nImport locally generated inventory data\n");
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
        $this->dispatch('/console/computer/import/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//p[@class="error"]');
        $this->assertXpathQueryContentContains('//h1', "\nImport locally generated inventory data\n");
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
        $this->dispatch('/console/computer/import/', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryContentContains(
            '//p[@class="error"]',
            "\nUpload error. Server http://example.net/server responded with error 500: reason_phrase\n"
        );
        $this->assertXpathQueryContentContains('//h1', "\nImport locally generated inventory data\n");
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
        $this->dispatch('/console/computer/import/', 'POST', $postData);
        $this->assertRedirectTo('/console/computer/index/');
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

        $this->dispatch('/console/computer/export/?id=1');
        $this->assertResponseStatusCode(200);
        $this->assertResponseHeaderContains('Content-Type', 'text/xml; charset="utf-8"');
        $this->assertResponseHeaderContains('Content-Disposition', 'attachment; filename="filename.xml"');
        $this->assertResponseHeaderContains('Content-Length', strlen($xmlContent));
        $this->assertEquals($xmlContent, $this->getResponse()->getContent());
    }
}
