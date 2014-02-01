<?php
/**
 * Tests for DuplicatesController
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
 * Tests for DuplicatesController
 */
class DuplicatesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Duplicates mock
     * @var \Model\Computer\Duplicates
     */
    protected $_duplicates;

    /**
     * ShowDuplicates mock
     * @var \Console\Form\ShowDuplicates
     */
    protected $_showDuplicates;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_showDuplicates = $this->getMock('Console\Form\ShowDuplicates');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\DuplicatesController($this->_duplicates, $this->_showDuplicates);
    }

    /** {@inheritdoc} */
    public function testService()
    {
        $this->_overrideService('Model\Computer\Duplicates', $this->_duplicates);
        $this->_overrideService('Console\Form\ShowDuplicates', $this->_showDuplicates, 'FormElementManager');
        parent::testService();
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/duplicates/index/';

        // No duplicates should lead to a simple message.
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

        // Test missing/invalid "Criteria" parameters
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

        // Test with valid criteria - should render form
        $this->_duplicates = $this->getMockBuilder('Model\Computer\Duplicates')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc');
        $this->_showDuplicates->expects($this->once())
                              ->method('render');

        $this->dispatch($url, 'GET', array('criteria' => 'Name'));
        $this->assertResponseStatusCode(200);
    }

    /**
     * Tests for mergeAction()
     */
    public function testMergeAction()
    {
        $url = '/console/duplicates/merge/';

        $this->_duplicates->expects($this->once())
                          ->method('merge')
                          ->with(array(1, 2), true, true, false);

        // Test valid selection
        $this->dispatch(
            $url,
            'POST',
            array(
                'computers' => array(1, 2),
                'mergeCustomFields' => '1',
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

        // GET request should display confirmation form.
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
