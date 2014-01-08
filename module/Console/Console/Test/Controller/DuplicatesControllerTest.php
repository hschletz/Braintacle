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
     * @var \Model\Computer\Duplicates
     */
    protected $_duplicates;

    /** {@inheritdoc} */
    public function _createController()
    {
        return new \Console\Controller\DuplicatesController($this->_config, $this->_duplicates);
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
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(0));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'No duplicates present.');

        // Duplicates should lead to a list with 4 hyperlinks.
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(2));
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQueryCount('td a[href*="/console/duplicates/show/?criteria="]', 4);
        $this->assertQueryContentContains('td a[href*="/console/duplicates/show/?criteria="]', "\n2\n");
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

        // Test missing/invalid "Criteria" parameters
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->any())
                          ->method('find')
                          ->with($this->logicalOr($this->isNull(), $this->identicalTo('invalid')), 'Id', 'asc')
                          ->will($this->throwException(new \InvalidArgumentException('Invalid criteria')));

        try {
            $this->dispatch($url);
            $this->fail('showAction() should have thrown an Exception on missing parameter "criteria"');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid criteria', $e->getMessage());
        }
        try {
            $this->dispatch($url, 'GET', array('criteria' => 'invalid'));
            $this->fail('showAction() should have thrown an Exception on invalid parameter "criteria"');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid criteria', $e->getMessage());
        }

        // Test with 'Name' - actual criteria are not relevant for this test.
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc')
                          ->will($this->returnValue($computers));

        $this->dispatch($url, 'GET', array('criteria' => 'Name'));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('input[type="checkbox"][name="computers[]"][value="2"]'); // Checkbox with Id

        // Test content of criteria columns
        $this->assertQueryContentContains(
            'td a[href="/console/computer/userdefined/?id=2"]',
            'Test2'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=MacAddress&value=00:00:5E:00:53:00"]',
            '00:00:5E:00:53:00'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=Serial&value=12345678"]',
            '12345678'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/duplicates/allow/?criteria=AssetTag&value=abc"]',
            'abc'
        );

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

        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->once())
                          ->method('merge')
                          ->with(array(1, 2), true, true, false);

        // Test valid selection
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

        // Test without selection
        $this->dispatch($url, 'POST');
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'At least 2 different computers have to be selected.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentInfoMessages()
        );

        // Test with only 1 selected computer
        $this->dispatch($url, 'POST', array('computers' => array(1)));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'At least 2 different computers have to be selected.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentInfoMessages()
        );

        // Test with only 1 multiply selected computer
        $this->dispatch($url, 'POST', array('computers' => array(1, 1)));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'At least 2 different computers have to be selected.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentInfoMessages()
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
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->never())
                          ->method('allow');
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');

        // POST request with "No" should redirect to criteria index page.
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/duplicates/show/?criteria=Serial');

        // POST request with "Yes" should exclude criteria and redirect to duplicates index page.
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->once())
                          ->method('allow')
                          ->with('Serial', '12345678');
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            '"12345678" is no longer considered duplicate.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }
}
