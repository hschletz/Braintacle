<?php
/**
 * Tests for DuplicatesController
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

namespace Console\Test\Controller;

/**
 * Tests for DuplicatesController
 */
class DuplicatesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Computer mock
     * @var \Model_Computer
     */
    protected $_computer;

    /** {@inheritdoc} */
    public function _createController()
    {
        return new \Console\Controller\DuplicatesController($this->_config, $this->_computer);
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/duplicates/index/';
        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();

        // No duplicates should lead to a simple message.
        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->exactly(4))
                        ->method('findDuplicates')
                        ->with($this->anything(), true)
                        ->will($this->returnValue(0));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'No duplicates present.');

        // Duplicates should lead to a list with 4 hyperlinks.
        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->exactly(4))
                        ->method('findDuplicates')
                        ->with($this->anything(), true)
                        ->will($this->returnValue(2));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryCount('dd a[href*="/console/duplicates/show/?criteria="]', 4);
        $this->assertQueryContentContains('dd a[href*="/console/duplicates/show/?criteria="]', "\n2\n");
    }

    /**
     * Tests for showAction()
     */
    public function testShowAction()
    {
        $url = '/console/duplicates/show/';
        $now = \Zend_Date::now();
        $computers = array(
            array(
                'Id' => 1,
                'Name' => 'Test1',
                'NetworkInterface.MacAddress' => '00:00:5E:00:53:00', // Reserved for documentation purposes
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

        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();
        $this->_config->expects($this->any())
                      ->method('__get')
                      ->will(
                          $this->returnValueMap(
                              array(
                                array('defaultMergeUserdefined', '1'),
                                array('defaultMergeGroups', '1'),
                                array('defaultMergePackages', '0'),
                              )
                          )
                      );

        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->any())
                        ->method('findDuplicates')
                        ->with($this->anything(), false, $this->anything(), $this->anything())
                        ->will(
                            $this->returnCallback(
                                function($criteria) use ($computers) {
                                    switch ($criteria) {
                                        case 'Name':
                                        case 'MacAddress':
                                        case 'Serial':
                                        case 'AssetTag':
                                            return $computers;
                                            break;
                                        default:
                                            throw new \InvalidArgumentException(
                                                'Invalid criteria: ' . $criteria
                                            );
                                    }
                                }
                            )
                        );

        // Test missing/invelid "Criteria" parameters
        try {
            $this->dispatch($url);
            $this->fail('showAction() should have thrown an Exceptopn on missing parameter "criteria"');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid criteria: ', $e->getMessage());
        }
        try {
            $this->dispatch($url, 'GET', array('criteria' => 'invalid'));
            $this->fail('showAction() should have thrown an Exceptopn on invalid parameter "criteria"');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid criteria: invalid', $e->getMessage());
        }

        // Test with all valid criteria
        $this->dispatch($url, 'GET', array('criteria' => 'Name'));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('input[type="checkbox"][name="computers[]"][value="2"]'); // Checkbox with Id
        $this->assertQueryContentContains(
            'td a[href="/console/computer/userdefined/?id=2"]',
            'Test2'
        ); // Name
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n"); // MacAddress
        $this->assertQueryContentContains('td', "\n12345678\n"); // Serial
        $this->assertQueryContentContains('td', "\nabc\n"); // AssetTag

        $this->dispatch($url, 'GET', array('criteria' => 'MacAddress'));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('input[type="checkbox"][name="computers[]"][value="2"]'); // Checkbox with Id
        $this->assertQueryContentContains(
            'td a[href="/console/computer/userdefined/?id=2"]',
            'Test2'
        ); // Name
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=MacAddress&value=00:00:5E:00:53:00"]',
            '00:00:5E:00:53:00'
        ); // MacAddress
        $this->assertQueryContentContains('td', "\n12345678\n"); // Serial
        $this->assertQueryContentContains('td', "\nabc\n"); // AssetTag

        $this->dispatch($url, 'GET', array('criteria' => 'Serial'));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('input[type="checkbox"][name="computers[]"][value="2"]'); // Checkbox with Id
        $this->assertQueryContentContains(
            'td a[href="/console/computer/userdefined/?id=2"]',
            'Test2'
        ); // Name
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n"); // MacAddress
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=Serial&value=12345678"]',
            '12345678'
        ); // Serial
        $this->assertQueryContentContains('td', "\nabc\n"); // AssetTag

        $this->dispatch($url, 'GET', array('criteria' => 'AssetTag'));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('input[type="checkbox"][name="computers[]"][value="2"]'); // Checkbox with Id
        $this->assertQueryContentContains(
            'td a[href="/console/computer/userdefined/?id=2"]',
            'Test2'
        ); // Name
        $this->assertQueryContentContains('td', "\n00:00:5E:00:53:00\n"); // MacAddress
        $this->assertQueryContentContains('td', "\n12345678\n"); // Serial
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=AssetTag&value=abc"]',
            'abc'
        ); // AssetTag

        // Test state of the 3 merge option checkboxes (depending on \Model\Config mock)
        $this->assertQuery('input[name="mergeUserdefined"][checked="checked"]');
        $this->assertQuery('input[name="mergeGroups"][checked="checked"]');
        // This should be unchecked. Test for presence first, then confirm that it's not checked.
        $this->assertQuery('input[name="mergePackages"]');
        $this->assertNotQuery('input[name="mergePackages"][checked="checked"]');
    }

    /**
     * Tests for mergeAction()
     */
    public function testMergeAction()
    {
        $url = '/console/duplicates/merge/';

        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();

        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->once())
                        ->method('mergeComputers')
                        ->with(array(1, 2), true, true, false);

        $this->dispatch(
            $url,
            'POST',
            array(
                'computers' => array(1, 2),
                'mergeUserdefined' => '1',
                'mergeGroups' => '1',
            )
        );
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'The selected computers have been merged.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );

        // GET request should throw exception.
        $this->setExpectedException('RuntimeException', 'Action "merge" can only be invoked via POST');
        $this->dispatch($url);
    }

    /**
     * Tests for allowAction()
     */
    public function testAllowAction()
    {
        $url = '/console/duplicates/allow/?criteria=Serial&value=12345678';

        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();

        // GET request should display confirmation form.
        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->never())
                        ->method('allowDuplicates');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request with "No" should redirect to criteria index page.
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/duplicates/show/?criteria=Serial');

        // POST request with "Yes" should exclude criteria and redirect to duplicates index page.
        $this->_computer = $this->getMock('Model_Computer');
        $this->_computer->expects($this->once())
                        ->method('allowDuplicates')
                        ->with('Serial', '12345678');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            '"12345678" is no longer considered duplicate.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }
}
