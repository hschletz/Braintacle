<?php
/**
 * Tests for ClientConfig form
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for ClientConfig form
 */
class ClientConfigTest extends \Console\Test\AbstractFormTest
{
    /**
     * Client mock
     * @var \Model\Client\Client
     */
    protected $_client;

    /**
     * Group mock
     * @var \Model\Group\Group
     */
    protected $_group;

    public function setUp()
    {
        $this->_client = $this->createMock('Model\Client\Client');
        $this->_group = $this->createMock('Model\Group\Group');
        parent::setUp();
    }

    public function testInit()
    {
        $agent = $this->_form->get('Agent');
        $this->assertInstanceOf('Zend\Form\Fieldset', $agent);

        $this->assertInstanceOf('Zend\Form\Element\Text', $agent->get('contactInterval'));
        $this->assertEquals(5, $agent->get('contactInterval')->getAttribute('size'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $agent->get('inventoryInterval'));
        $this->assertEquals(5, $agent->get('inventoryInterval')->getAttribute('size'));

        $download = $this->_form->get('Download');
        $this->assertInstanceOf('Zend\Form\Fieldset', $download);

        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $download->get('packageDeployment'));
        $this->assertEquals('toggle(this)', $download->get('packageDeployment')->getAttribute('onchange'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $download->get('downloadPeriodDelay'));
        $this->assertEquals(5, $download->get('downloadPeriodDelay')->getAttribute('size'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $download->get('downloadCycleDelay'));
        $this->assertEquals(5, $download->get('downloadCycleDelay')->getAttribute('size'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $download->get('downloadFragmentDelay'));
        $this->assertEquals(5, $download->get('downloadFragmentDelay')->getAttribute('size'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $download->get('downloadMaxPriority'));
        $this->assertEquals(5, $download->get('downloadMaxPriority')->getAttribute('size'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $download->get('downloadTimeout'));
        $this->assertEquals(5, $download->get('downloadTimeout')->getAttribute('size'));

        $scan = $this->_form->get('Scan');
        $this->assertInstanceOf('Zend\Form\Fieldset', $scan);

        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $scan->get('allowScan'));
        $this->assertEquals('toggle(this)', $scan->get('allowScan')->getAttribute('onchange'));

        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $scan->get('scanThisNetwork'));
        $this->assertSame('', $scan->get('scanThisNetwork')->getEmptyOption());

        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $scan->get('scanSnmp'));

        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testRender()
    {
        $view = $this->_createView();
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('renderFieldset'))->getMock();
        $form->expects($this->once())
             ->method('renderFieldset')
             ->with($view, $form)
             ->will($this->returnValue('fieldset'));
        $this->assertContains('fieldset', $form->render($view));

        $headScript = $view->headScript()->toString();
        $this->assertContains('function toggle(element)', $headScript);
        $this->assertContains('function toggleByName(name)', $headScript);

        $bodyOnLoad = $view->placeholder('BodyOnLoad');
        $this->assertContains('toggleByName("Download[packageDeployment]")', $bodyOnLoad);
        $this->assertContains('toggleByName("Scan[allowScan]")', $bodyOnLoad);
    }

    public function testRenderFieldsetFieldset()
    {
        $this->_form->setClientObject($this->_group);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Agent'));
        $document = new \Zend\Dom\Document($html);

        $this->assertCount(2, Query::execute('//fieldset/div[@class="table"]//input[@type="text"]', $document));
        $queryResult = Query::execute(
            "//fieldset/div[@class='table']/label/span[@class='label']" .
            "[text()='\nAgenten-Kontaktintervall (in Stunden)\n']",
            $document
        );
        $this->assertCount(1, $queryResult);
    }

    public function testRenderFieldsetTextDefaultsForGroup()
    {
        $defaults = array(
            array('contactInterval', 'default&1'),
            array('inventoryInterval', 'default&2'),
        );
        $this->_group->expects($this->any())
                     ->method('getDefaultConfig')
                     ->will($this->returnValueMap($defaults));

        $this->_form->setClientObject($this->_group);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Agent'));
        $document = new \Zend\Dom\Document($html);

        $query = "//input[@name='Agent[%s]']/following-sibling::text()[string()='(Standard: %s)\n']";
        $this->assertCount(1, Query::execute(sprintf($query, 'contactInterval', 'default&1'), $document));
        $this->assertCount(1, Query::execute(sprintf($query, 'inventoryInterval', 'default&2'), $document));
    }

    public function testRenderFieldsetTextDefaultsForClient()
    {
        $defaults = array(
            array('contactInterval', 'default&1'),
            array('inventoryInterval', 'default&2'),
        );
        $effective = array(
            array('contactInterval', 'effective&1'),
            array('inventoryInterval', 'effective&2'),
        );
        $this->_client->method('getDefaultConfig')->will($this->returnValueMap($defaults));
        $this->_client->method('getEffectiveConfig')->will($this->returnValueMap($effective));
        $this->_client->method('getItems')->willReturn(array());

        $this->_form->setClientObject($this->_client);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Agent'));
        $document = new \Zend\Dom\Document($html);

        $query = "//input[@name='Agent[%s]']/following-sibling::text()[string()='(Standard: %s, Effektiv: %s)\n']";
        $this->assertCount(
            1,
            Query::execute(sprintf($query, 'contactInterval', 'default&1', 'effective&1'), $document)
        );
        $this->assertCount(
            1,
            Query::execute(sprintf($query, 'inventoryInterval', 'default&2', 'effective&2'), $document)
        );
    }

    public function testRenderFieldsetCheckboxDefaultsForGroup()
    {
        $defaults = array(
            array('allowScan', '1'),
            array('scanSnmp', '0'),
        );
        $this->_group->expects($this->any())
                     ->method('getDefaultConfig')
                     ->will($this->returnValueMap($defaults));

        $this->_form->setClientObject($this->_group);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Scan'));
        $document = new \Zend\Dom\Document($html);

        $query = "//input[@name='Scan[%s]']/following-sibling::text()[string()='(Standard: %s)\n']";
        $this->assertCount(1, Query::execute(sprintf($query, 'allowScan', 'Ja'), $document));
        $this->assertCount(1, Query::execute(sprintf($query, 'scanSnmp', 'Nein'), $document));
    }

    public function testRenderFieldsetCheckboxDefaultsForClient()
    {
        $defaults = array(
            array('allowScan', '1'),
            array('scanSnmp', '0'),
        );
        $effective = array(
            array('allowScan', '0'),
            array('scanSnmp', '1'),
        );
        $this->_client->method('getDefaultConfig')->will($this->returnValueMap($defaults));
        $this->_client->method('getEffectiveConfig')->will($this->returnValueMap($effective));
        $this->_client->method('getItems')->willReturn(array());

        $this->_form->setClientObject($this->_client);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Scan'));
        $document = new \Zend\Dom\Document($html);

        $query = "//input[@name='Scan[%s]']/following-sibling::text()[string()='(Standard: %s, Effektiv: %s)\n']";
        $this->assertCount(1, Query::execute(sprintf($query, 'allowScan', 'Ja', 'Nein'), $document));
        $this->assertCount(1, Query::execute(sprintf($query, 'scanSnmp', 'Nein', 'Ja'), $document));
    }

    public function testRenderFieldsetWithNetworks()
    {
        $this->_client->method('getItems')->will($this->returnValue(array(array('Subnet' => '192.9.2.0'))));

        $this->_form->setClientObject($this->_client);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Scan'));
        $document = new \Zend\Dom\Document($html);

        $this->assertCount(1, Query::execute('//select', $document));
    }

    public function testRenderFieldsetNoNetworks()
    {
        $this->_form->setClientObject($this->_group);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Scan'));
        $document = new \Zend\Dom\Document($html);

        $this->assertCount(0, Query::execute('//select', $document));
    }

    public function testRenderFieldsetMessages()
    {
        $this->_form->setClientObject($this->_group);
        $this->_form->setMessages(array('Agent' => array('contactInterval' => array('message&1'))));
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form->get('Agent'));
        $document = new \Zend\Dom\Document($html);

        $this->assertCount(1, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(1, Query::execute('//ul[@class="error"]', $document));
        $this->assertCount(1, Query::execute('//ul[@class="error"]/li', $document));
        $this->assertCount(1, Query::execute('//ul[@class="error"]/li[text()="message&1"]', $document));
    }

    public function testRenderFieldsetForm()
    {
        $this->_form->setClientObject($this->_group);
        $this->_form->prepare();

        $view = $this->_createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $document = new \Zend\Dom\Document($html);

        $this->assertCount(3, Query::execute('//div[@class="table"]/fieldset', $document));
        $this->assertCount(1, Query::execute("//fieldset[1]/legend[text()='\nAgent\n']", $document));
        $this->assertCount(1, Query::execute("//fieldset[2]/legend[text()='\nDownload\n']", $document));
        $this->assertCount(1, Query::execute("//fieldset[3]/legend[text()='\nNetzwerk-Scans\n']", $document));
        $this->assertCount(1, Query::execute('//div[@class="table"]/input[@type="submit"]', $document));
    }

    public function testSetDataWithoutClientObject()
    {
        $this->setExpectedException('LogicException', 'No client or group object set');
        $this->_form->setData(array());
    }

    public function testSetClientObjectGroup()
    {
        $this->_form->setClientObject($this->_group);
        $scan = $this->_form->get('Scan');
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
        $this->_form->setClientObject($this->_client);
        $scan = $this->_form->get('Scan');
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
        $this->_form->setClientObject($this->_client);
        $scan = $this->_form->get('Scan');
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
        $this->_form->setClientObject($this->_client);
        $scan = $this->_form->get('Scan');
        $scanThisNetwork = $scan->get('scanThisNetwork');
        $this->assertFalse($scanThisNetwork->getAttribute('disabled'));
        $this->assertEquals(array('192.0.2.0', '198.51.100.0'), $scanThisNetwork->getValueOptions());
    }

    public function testInputFilterAgentEmpty()
    {
        $data = array('Agent' => array('contactInterval' => '', 'inventoryInterval' => ''));
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Agent');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterAgentLocalizedInput()
    {
        $data = array('Agent' => array('contactInterval' => '1.234', 'inventoryInterval' => '5.678'));
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Agent');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(
            array('contactInterval' => '1234', 'inventoryInterval' => '5678'),
            $this->_form->getData()['Agent']
        );
    }

    public function testInputFilterAgentMinValue()
    {
        $data = array('Agent' => array('contactInterval' => '1', 'inventoryInterval' => '-1'));
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Agent');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterAgentValuesTooSmall()
    {
        $data = array('Agent' => array('contactInterval' => '0', 'inventoryInterval' => '-2'));
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Agent');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'contactInterval' => array('callbackValue' => "Die Eingabe ist nicht größer oder gleich '1'"),
            'inventoryInterval' => array('callbackValue' => "Die Eingabe ist nicht größer oder gleich '-1'"),
        );
        $this->assertEquals($messages, $this->_form->getMessages()['Agent']);
    }

    public function testInputFilterAgentNotInteger()
    {
        $data = array('Agent' => array('contactInterval' => '1,234', 'inventoryInterval' => '5,678'));
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Agent');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Agent'];
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(
            array(
                'packageDeployment' => '1',
                'downloadPeriodDelay' => '1111',
                'downloadCycleDelay' => '2222',
                'downloadFragmentDelay' => '3333',
                'downloadMaxPriority' => '4444',
                'downloadTimeout' => '5555',
            ),
            $this->_form->getData()['Download']
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $message = array('callbackValue' => "Die Eingabe ist nicht größer oder gleich '1'");
        $messages = array(
            'downloadPeriodDelay' => $message,
            'downloadCycleDelay' => $message,
            'downloadFragmentDelay' => $message,
            'downloadMaxPriority' => $message,
            'downloadTimeout' => $message,
        );
        $this->assertEquals($messages, $this->_form->getMessages()['Download']);
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Download'];
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
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
        $this->_form->setClientObject($this->_group);
        $this->_form->setValidationGroup('Download');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
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
        $this->_form->setClientObject($this->_client);
        $this->assertTrue($this->_form->get('Scan')->has('scanThisNetwork'));
        $this->_form->setValidationGroup('Scan');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
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
        $this->_form->setClientObject($this->_client);
        $this->_form->setValidationGroup('Scan');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
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
        $this->_form->setValidationGroup('Agent', 'Download', 'Scan');
        $this->_form->setClientObject($this->_group);
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->_form->process();
    }
}
