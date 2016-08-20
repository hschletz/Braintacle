<?php
/**
 * Tests for ShowDuplicates
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

use Zend\Dom\Document\Query as Query;

/**
 * Tests for ShowDuplicates
 */
class ShowDuplicatesTest extends \Console\Test\AbstractFormTest
{
    /**
     * Config mock object
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Set up Config mock object
     */
    public function setUp()
    {
        $this->_config = $this->createMock('Model\Config');
        $this->_config->expects($this->any())
                      ->method('__get')
                      ->will(
                          $this->returnValueMap(
                              array(
                                array('defaultMergeCustomFields', 1),
                                array('defaultMergeGroups', 1),
                                array('defaultMergePackages', 0),
                              )
                          )
                      );
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\ShowDuplicates(null, array('config' => $this->_config));
        $form->init();
        return $form;
    }

    /**
     * Tests for init()
     */
    public function testInit()
    {
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $this->_form->get('mergeCustomFields'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $this->_form->get('mergeGroups'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $this->_form->get('mergePackages'));
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('submit'));
    }

    /**
     * Tests for input filter provided by init()
     */
    public function testInputFilter()
    {
        // Test without "clients" array (happens when no client is selected)
        $data = array(
            'mergeCustomFields' => '1',
            'mergeGroups' => '1',
            'mergePackages' => '0',
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with empty "clients" array
        $data['clients'] = array();
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with 2 identical clients
        $data['clients'] = array('1', '1');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with invalid array content
        $data['clients'] = array('1', 'a');
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with 2 identical clients + 1 extra
        $data['clients'] = array('1', '1', '2');
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        // Test filtered and validated data
        $this->assertEquals(array('1', '2'), array_values($this->_form->getData()['clients']));

        // Test invalid input on other elements to ensure that builtin input
        // filters are not overwritten
        $data['mergeGroups'] = '2';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test non-array input on "clients"
        $this->setExpectedException('InvalidArgumentException');
        $data['clients'] = '';
        $this->_form->setData($data);
        $this->_form->isValid();
    }

    public function testGetMessagesNoSelection()
    {
        // Test with invalid "clients" and "mergeGroups" fields
        $data = array(
            'mergeCustomFields' => '1',
            'mergeGroups' => '2',
            'mergePackages' => '0',
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->_form->isValid();

        $this->assertCount(2, $this->_form->getMessages());
        $this->assertCount(1, $this->_form->getMessages('mergeGroups'));
        $this->assertEquals(
            array('Es müssen mindestens 2 verschiedene Clients ausgewählt werden'),
            $this->_form->getMessages('clients')
        );
    }

    public function testGetMessagesInsufficientSelection()
    {
        $data = array(
            'clients' => array('1'),
            'mergeCustomFields' => '1',
            'mergeGroups' => '1',
            'mergePackages' => '0',
            'submit' => 'Merge selected client',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->_form->isValid();

        $this->assertCount(1, $this->_form->getMessages());
        $this->assertEquals(
            array('Es müssen mindestens 2 verschiedene Clients ausgewählt werden'),
            $this->_form->getMessages('clients')
        );
    }

    /**
     * Tests for render()
     */
    public function testRender()
    {
        $now = new \DateTime;
        $clients = array(
            array(
                'Id' => 1,
                'Name' => 'Test1',
                'NetworkInterface.MacAddress' => '00:00:5E:00:53:00',
                'Serial' => '12345678',
                'AssetTag' => 'abc',
                'LastContactDate' => $now,
            ),
            array(
                'Id' => 2,
                'Name' => 'Test2',
                'NetworkInterface.MacAddress' => '00:00:5E:00:53:00',
                'Serial' => '12345678',
                'AssetTag' => null,
                'LastContactDate' => $now,
            ),
        );

        $this->_form->setOptions(
            array(
                'clients' => $clients,
                'order' => 'Id',
                'direction' => 'asc',
            )
        );

        $output = $this->_form->render($this->_createView());

        // Test table content
        $document = new \Zend\Dom\Document($output);

        $result = Query::execute(
            '//td//a[@href="/console/client/customfields/?id=2"]',
            $document
        );
        $this->assertCount(1, $result);
        $this->assertEquals('Test2', $result[0]->nodeValue);

        $result = Query::execute(
            '//td//a[@href="/console/duplicates/allow/?criteria=MacAddress&value=00:00:5E:00:53:00"]',
            $document
        );
        $this->assertCount(2, $result);
        $this->assertEquals('00:00:5E:00:53:00', $result[0]->nodeValue);

        $result = Query::execute(
            '//td//a[@href="/console/duplicates/allow/?criteria=Serial&value=12345678"]',
            $document
        );
        $this->assertCount(2, $result);
        $this->assertEquals('12345678', $result[0]->nodeValue);

        $result = Query::execute(
            '//td//a[@href="/console/duplicates/allow/?criteria=AssetTag&value=abc"]',
            $document
        );
        $this->assertCount(1, $result);
        $this->assertEquals('abc', $result[0]->nodeValue);

        // 1 Empty cell resulting from NULL value
        $result = Query::execute(
            '//td[not(node())]',
            $document
        );
        $this->assertCount(1, $result);

        // Test state of the 3 merge option checkboxes (depending on \Model\Config mock)
        $result = Query::execute(
            '//input[@name="mergeCustomFields"][@checked="checked"]',
            $document
        );
        $this->assertCount(1, $result);
        $result = Query::execute(
            '//input[@name="mergeGroups"][@checked="checked"]',
            $document
        );
        $this->assertCount(1, $result);
        // This should be unchecked. Test for presence first, excluding "hidden"
        // element with same name, then confirm that it's not checked.
        $result = Query::execute(
            '//input[@type="checkbox"][@name="mergePackages"][not(@checked)]',
            $document
        );
        $this->assertCount(1, $result);
    }
}
