<?php
/**
 * Tests for ShowDuplicates
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

/**
 * Tests for ShowDuplicates
 */
class ShowDuplicatesTest extends \PHPUnit_Framework_TestCase
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
        parent::setUp();
        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();
        $this->_config->expects($this->any())
                      ->method('__get')
                      ->will(
                          $this->returnValueMap(
                              array(
                                array('defaultMergeCustomFields', '1'),
                                array('defaultMergeGroups', '1'),
                                array('defaultMergePackages', '0'),
                              )
                          )
                      );
    }

    /**
     * Tests for init()
     */
    public function testInit()
    {
        $form = new \Console\Form\ShowDuplicates(null, array('config' => $this->_config));
        $form->init();

        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $form->get('mergeCustomFields'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $form->get('mergeGroups'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $form->get('mergePackages'));
        $this->assertInstanceOf('\Zend\Form\Element\Submit', $form->get('submit'));
    }

    /**
     * Tests for render()
     */
    public function testRender()
    {
        $now = \Zend_Date::now();
        $computers = array(
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
                'AssetTag' => 'abc',
                'LastContactDate' => $now,
            ),
        );

        $form = new \Console\Form\ShowDuplicates;
        $form->setOptions(
            array(
                'config' => $this->_config,
                'computers' => $computers,
                'order' => 'Id',
                'direction' => 'asc',
            )
        );
        $form->init();

        $output = $form->render(\Library\Application::getService('ViewManager')->getRenderer());

        // Test table content
        $dom = new \Zend\Dom\Query;
        $dom->setDocumentHtml($output);

        $result = $dom->execute('td a[href="/console/computer/userdefined/?id=2"]');
        $this->assertCount(1, $result);
        $this->assertEquals('Test2', $result[0]->nodeValue);

        $result = $dom->execute('td a[href="/console/duplicates/allow/?criteria=MacAddress&value=00:00:5E:00:53:00"]');
        $this->assertCount(2, $result);
        $this->assertEquals('00:00:5E:00:53:00', $result[0]->nodeValue);

        $result = $dom->execute('td a[href="/console/duplicates/allow/?criteria=Serial&value=12345678"]');
        $this->assertCount(2, $result);
        $this->assertEquals('12345678', $result[0]->nodeValue);

        $result = $dom->execute('td a[href="/console/duplicates/allow/?criteria=AssetTag&value=abc"]');
        $this->assertCount(2, $result);
        $this->assertEquals('abc', $result[0]->nodeValue);

        // Test state of the 3 merge option checkboxes (depending on \Model\Config mock)
        $result = $dom->execute('input[name="mergeCustomFields"][checked="checked"]');
        $this->assertCount(1, $result);
        $result = $dom->execute('input[name="mergeGroups"][checked="checked"]');
        $this->assertCount(1, $result);
        // This should be unchecked. Test for presence first, excluding "hidden"
        // element with same name, then confirm that it's not checked.
        $result = $dom->execute('input[type="checkbox"][name="mergePackages"]');
        $this->assertCount(1, $result);
        $this->assertNotEquals('checked', $result[0]->getAttribute('checked'));
    }
}
