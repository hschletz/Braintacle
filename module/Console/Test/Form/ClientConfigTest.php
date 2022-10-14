<?php

/**
 * Tests for ClientConfig form
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

namespace Console\Test\Form;

use Console\Form\ClientConfig;
use Model\Client\Client;
use Model\Group\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for ClientConfig form
 */
class ClientConfigTest extends \Console\Test\AbstractFormTest
{
    /**
     * Client mock
     * @var MockObject|Client
     */
    protected $_client;

    /**
     * Group mock
     * @var MockObject|Group
     */
    protected $_group;

    public function setUp(): void
    {
        $this->_client = $this->createMock('Model\Client\Client');
        $this->_group = $this->createMock('Model\Group\Group');
        parent::setUp();
    }

    public function testInit()
    {
        $agent = $this->_form->get('Agent');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $agent);
        $this->assertEquals('Agent', $agent->getLabel());

        $this->assertInstanceOf('Laminas\Form\Element\Text', $agent->get('contactInterval'));
        $this->assertEquals(5, $agent->get('contactInterval')->getAttribute('size'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $agent->get('inventoryInterval'));
        $this->assertEquals(5, $agent->get('inventoryInterval')->getAttribute('size'));

        $download = $this->_form->get('Download');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $download);
        $this->assertEquals('Download', $download->getLabel());

        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $download->get('packageDeployment'));
        $this->assertEquals('toggle', $download->get('packageDeployment')->getAttribute('class'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $download->get('downloadPeriodDelay'));
        $this->assertEquals(5, $download->get('downloadPeriodDelay')->getAttribute('size'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $download->get('downloadCycleDelay'));
        $this->assertEquals(5, $download->get('downloadCycleDelay')->getAttribute('size'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $download->get('downloadFragmentDelay'));
        $this->assertEquals(5, $download->get('downloadFragmentDelay')->getAttribute('size'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $download->get('downloadMaxPriority'));
        $this->assertEquals(5, $download->get('downloadMaxPriority')->getAttribute('size'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $download->get('downloadTimeout'));
        $this->assertEquals(5, $download->get('downloadTimeout')->getAttribute('size'));

        $scan = $this->_form->get('Scan');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $scan);
        $this->assertEquals('Network scanning', $scan->getLabel());

        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $scan->get('allowScan'));
        $this->assertEquals('toggle', $scan->get('allowScan')->getAttribute('class'));

        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $scan->get('scanThisNetwork'));
        $this->assertSame('', $scan->get('scanThisNetwork')->getEmptyOption());

        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $scan->get('scanSnmp'));

        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testSetDataWithoutClientObject()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No client or group object set');
        $this->_form->setData(array());
    }

    public function testSetClientObjectGroup()
    {
        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);

        $scan = $form->get('Scan');
        $scanThisNetwork = $scan->get('scanThisNetwork');
        $this->assertTrue($scanThisNetwork->getAttribute('disabled'));
        $this->assertEmpty($scanThisNetwork->getValueOptions());
    }

    public function testSetClientObjectClientNoNetworks()
    {
        $this->_client->expects($this->once())
                      ->method('getItems')
                      ->with('NetworkInterface', 'Subnet')
                      ->willReturn(array());

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_client);

        $scan = $form->get('Scan');
        $scanThisNetwork = $scan->get('scanThisNetwork');
        $this->assertTrue($scanThisNetwork->getAttribute('disabled'));
        $this->assertEmpty($scanThisNetwork->getValueOptions());
    }

    public function testSetClientObjectClientNoScannableNetworks()
    {
        $networks = array(
            array('Subnet' => '0.0.0.0'),
        );
        $this->_client->expects($this->once())
                      ->method('getItems')
                      ->with('NetworkInterface', 'Subnet')
                      ->willReturn($networks);


        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_client);

        $scan = $form->get('Scan');
        $scanThisNetwork = $scan->get('scanThisNetwork');
        $this->assertTrue($scanThisNetwork->getAttribute('disabled'));
        $this->assertEmpty($scanThisNetwork->getValueOptions());
    }

    public function testSetClientObjectClientWithScannableNetworks()
    {
        $networks = array(
            array('Subnet' => '0.0.0.0'),
            array('Subnet' => '192.0.2.0'),
            array('Subnet' => '192.0.2.0'),
            array('Subnet' => '198.51.100.0'),
        );
        $this->_client->expects($this->once())
                      ->method('getItems')
                      ->with('NetworkInterface', 'Subnet')
                      ->willReturn($networks);

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_client);

        $scan = $form->get('Scan');
        $scanThisNetwork = $scan->get('scanThisNetwork');
        $this->assertFalse($scanThisNetwork->getAttribute('disabled'));
        $this->assertEquals(array('192.0.2.0', '198.51.100.0'), $scanThisNetwork->getValueOptions());
    }

    public function testGetClientObject()
    {
        $form = new ClientConfig();

        $object = new \ReflectionProperty($this->_form, '_object');
        $object->setAccessible(true);
        $object->setValue($form, $this->_client);

        $this->assertEquals($this->_client, $form->getClientObject());
    }

    public function testInputFilterAgentEmpty()
    {
        $data = array('Agent' => array('contactInterval' => '', 'inventoryInterval' => ''));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Agent']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterAgentLocalizedInput()
    {
        $data = array('Agent' => array('contactInterval' => '1.234', 'inventoryInterval' => '5.678'));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Agent']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $this->assertEquals(
            array('contactInterval' => '1234', 'inventoryInterval' => '5678'),
            $form->getData()['Agent']
        );
    }

    public function testInputFilterAgentMinValue()
    {
        $data = array('Agent' => array('contactInterval' => '1', 'inventoryInterval' => '-1'));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Agent']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterAgentValuesTooSmall()
    {
        $data = array('Agent' => array('contactInterval' => '0', 'inventoryInterval' => '-2'));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Agent']);
        $form->setData($data);
        $this->assertFalse($form->isValid());
        $messages = array(
            'contactInterval' => array('callbackValue' => "TRANSLATE(The input is not greater than or equal to '1')"),
            'inventoryInterval' => array('callbackValue' => "TRANSLATE(The input is not greater than or equal to '-1')"),
        );
        $this->assertEquals($messages, $form->getMessages()['Agent']);
    }

    public function testInputFilterAgentNotInteger()
    {
        $data = array('Agent' => array('contactInterval' => '1,234', 'inventoryInterval' => '5,678'));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Agent']);
        $form->setData($data);
        $this->assertFalse($form->isValid());
        $messages = $form->getMessages()['Agent'];
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['contactInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['contactInterval']);
        $this->assertCount(1, $messages['inventoryInterval']);
        $this->assertArrayHasKey('callbackValue', $messages['inventoryInterval']);
    }

    public function testInputFilterDownloadEmpty()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '',
                'downloadCycleDelay' => '',
                'downloadFragmentDelay' => '',
                'downloadMaxPriority' => '',
                'downloadTimeout' => '',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterDownloadLocalizedInput()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1.111',
                'downloadCycleDelay' => '2.222',
                'downloadFragmentDelay' => '3.333',
                'downloadMaxPriority' => '4.444',
                'downloadTimeout' => '5.555',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $this->assertEquals(
            array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1111',
                'downloadCycleDelay' => '2222',
                'downloadFragmentDelay' => '3333',
                'downloadMaxPriority' => '4444',
                'downloadTimeout' => '5555',
            ),
            $form->getData()['Download']
        );
    }

    public function testInputFilterDownloadMinValue()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1',
                'downloadCycleDelay' => '1',
                'downloadFragmentDelay' => '1',
                'downloadMaxPriority' => '1',
                'downloadTimeout' => '1',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterDownloadValuesTooSmall()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '0',
                'downloadCycleDelay' => '0',
                'downloadFragmentDelay' => '0',
                'downloadMaxPriority' => '0',
                'downloadTimeout' => '0',
            )
        );
        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertFalse($form->isValid());
        $message = array('callbackValue' => "TRANSLATE(The input is not greater than or equal to '1')");
        $messages = array(
            'downloadPeriodDelay' => $message,
            'downloadCycleDelay' => $message,
            'downloadFragmentDelay' => $message,
            'downloadMaxPriority' => $message,
            'downloadTimeout' => $message,
        );
        $this->assertEquals($messages, $form->getMessages()['Download']);
    }

    public function testInputFilterDownloadNotInteger()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1,111',
                'downloadCycleDelay' => '2,222',
                'downloadFragmentDelay' => '3,333',
                'downloadMaxPriority' => '4,444',
                'downloadTimeout' => '5,555',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertFalse($form->isValid());
        $messages = $form->getMessages()['Download'];
        $this->assertCount(5, $messages);
        $this->assertCount(1, $messages['downloadPeriodDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadPeriodDelay']);
        $this->assertCount(1, $messages['downloadCycleDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadCycleDelay']);
        $this->assertCount(1, $messages['downloadFragmentDelay']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadFragmentDelay']);
        $this->assertCount(1, $messages['downloadMaxPriority']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadMaxPriority']);
        $this->assertCount(1, $messages['downloadTimeout']);
        $this->assertArrayHasKey('callbackValue', $messages['downloadTimeout']);
    }

    public function testInputFilterDownloadValuesTooSmallIgnored()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '0',
                'downloadPeriodDelay' => '0',
                'downloadCycleDelay' => '0',
                'downloadFragmentDelay' => '0',
                'downloadMaxPriority' => '0',
                'downloadTimeout' => '0',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterDownloadNotIntegerIgnored()
    {
        $data = array(
            'Download' => array(
                'packageDeployment' => '0',
                'downloadPeriodDelay' => '1,111',
                'downloadCycleDelay' => '2,222',
                'downloadFragmentDelay' => '3,333',
                'downloadMaxPriority' => '4,444',
                'downloadTimeout' => '5,555',
            )
        );

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_group);
        $form->setValidationGroup(['Download']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterScanEmptyNetwork()
    {
        $data = array(
            'Scan' => array(
                'allowScan' => '1',
                'scanThisNetwork' => '',
                'scanSnmp' => '1',
            )
        );
        $this->_client->expects($this->once())
                      ->method('getItems')
                      ->with('NetworkInterface', 'Subnet')
                      ->willReturn(array(array('Subnet' => '192.0.2.0')));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_client);
        $this->assertTrue($form->get('Scan')->has('scanThisNetwork'));
        $form->setValidationGroup(['Scan']);
        $form->setData($data);
        $this->assertTrue($form->isValid());
    }

    public function testInputFilterScanInvalidNetwork()
    {
        $data = array(
            'Scan' => array(
                'allowScan' => '1',
                'scanThisNetwork' => 'invalid',
                'scanSnmp' => '1',
            )
        );
        $this->_client->expects($this->once())
                      ->method('getItems')
                      ->with('NetworkInterface', 'Subnet')
                      ->willReturn(array(array('Subnet' => '192.0.2.0')));

        $form = new ClientConfig();
        $form->init();
        $form->setClientObject($this->_client);
        $form->setValidationGroup(['Scan']);
        $form->setData($data);
        $this->assertFalse($form->isValid());
    }

    public function testProcess()
    {
        $data = array(
            'Agent' => array(
                'contactInterval' => '1.234',
                'inventoryInterval' => ''
            ),
            'Download' => array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1.111',
                'downloadCycleDelay' => '2.222',
                'downloadFragmentDelay' => '3.333',
                'downloadMaxPriority' => '4.444',
                'downloadTimeout' => '',
            ),
            'Scan' => array(
                'allowScan' => '0',
                'scanSnmp' => '1',
            )
        );
        $this->_group->expects($this->exactly(11))
                     ->method('setConfig')
                     ->withConsecutive(
                         array('contactInterval', $this->identicalTo(1234)),
                         array('inventoryInterval', $this->isNull()),
                         array('downloadPeriodDelay', $this->identicalTo(1111)),
                         array('downloadCycleDelay', $this->identicalTo(2222)),
                         array('downloadFragmentDelay', $this->identicalTo(3333)),
                         array('downloadMaxPriority', $this->identicalTo(4444)),
                         array('downloadTimeout', $this->isNull()),
                         array('packageDeployment', $this->identicalTo('1')),
                         array('allowScan', $this->identicalTo('0')),
                         array('scanThisNetwork', $this->isNull()),
                         array('scanSnmp', $this->isNull())
                     );

        $form = new ClientConfig();
        $form->init();
        $form->setValidationGroup(['Agent', 'Download', 'Scan']);
        $form->setClientObject($this->_group);
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $form->process();
    }
}
