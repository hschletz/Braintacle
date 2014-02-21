<?php
/**
 * Tests for SoftwareController
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
 * Tests for SoftwareController
 */
class SoftwareControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Software mock
     * @var \Model_Software
     */
    protected $_software;

    /**
     * Form mock
     * @var \Form_SoftwareFilter
     */
    protected $_form;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_software = $this->getMock('Model_Software');
        $this->_form = $this->getMock('Form_SoftwareFilter');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\SoftwareController($this->_software, $this->_form);
    }

    /**
     * Tests for indexAction()
     */
    public function testIndexAction()
    {
        $url = '/console/software/index/'; //TODO: all filters
        $software = array(
            array('Name' => 'name1', 'RawName' => 'raw_name1', 'NumComputers' => 1),
            array('Name' => 'name2', 'RawName' => 'raw_name2', 'NumComputers' => 2),
        );
        $filters = array(
            'Os' => 'windows',
            'Status' => 'accepted',
            'Unique' => null,
        );
        $session = new \Zend\Session\Container('ManageSoftware');

        // Test default filter ('accepted')
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($software));

        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('accepted');
        $this->_form->expects($this->once())->method('toHtml');

        unset($session->filter);
        $this->dispatch($url);
        $this->assertEquals('accepted', $session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertNotQueryContentContains(
            'td a',
            'Accept'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/software/ignore/?name=raw_name2"]',
            'Ignore'
        );
        $this->assertQueryContentContains(
            'td',
            "\nname2\n"
        );
        $this->assertQueryContentContains(
            'td a[href*="/console/computer/index/"][href*="search=raw_name2"]',
            '2'
        );

        // Test 'ignored' filter
        $filters['Status'] = 'ignored';
        $this->_software = $this->getMock('Model_Software');
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($software));

        $this->_form = $this->getMock('Form_SoftwareFilter');
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('ignored');
        $this->_form->expects($this->once())->method('toHtml');

        $this->dispatch($url, 'GET', array('filter' => 'ignored'));
        $this->assertEquals('ignored', $session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'td a[href="/console/software/accept/?name=raw_name2"]',
            'Accept'
        );
        $this->assertNotQueryContentContains(
            'td a',
            'Ignore'
        );

        // Test 'new' filter
        $filters['Status'] = 'new';
        $this->_software = $this->getMock('Model_Software');
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($software));

        $this->_form = $this->getMock('Form_SoftwareFilter');
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('new');
        $this->_form->expects($this->once())->method('toHtml');

        $this->dispatch($url, 'GET', array('filter' => 'new'));
        $this->assertEquals('new', $session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'td a[href="/console/software/accept/?name=raw_name2"]',
            'Accept'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/software/ignore/?name=raw_name2"]',
            'Ignore'
        );

        // Test 'all' filter
        $filters['Status'] = 'all';
        $this->_software = $this->getMock('Model_Software');
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($software));

        $this->_form = $this->getMock('Form_SoftwareFilter');
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('all');
        $this->_form->expects($this->once())->method('toHtml');

        $this->dispatch($url, 'GET', array('filter' => 'all'));
        $this->assertEquals('all', $session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertNotQueryContentContains(
            'td a',
            'Accept'
        );
        $this->assertNotQueryContentContains(
            'td a',
            'Ignore'
        );
    }

    /**
     * Tests for accept action
     */
    public function testAcceptAction()
    {
        $this->_testManageAction('accept');
    }

    /**
     * Tests for ignore action
     */
    public function testIgnoreAction()
    {
        $this->_testManageAction('ignore');
    }

    /**
     * Common tests for accept/ignore actions
     */
    protected function _testManageAction($action)
    {
        $tmUtf16 = chr(0xc2) . chr(0x99); // UTF-16 representation of TM symbol
        $tmUtf8  = chr(0xe2) . chr(0x84) . chr(0xa2); // UTF-8 representation of TM symbol
        $url = "/console/software/$action/?name=" . urlencode($tmUtf16);
        $this->_sessionSetup = array(
            'ManageSoftware' => array('filter' => 'test')
        );

        // GET request should display form
        $this->dispatch($url);
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
        $this->assertQueryContentRegex('p', "/$tmUtf8/");

        // Cancelled POST request should redirect and do nothing else
        $this->_software->expects($this->never())
                        ->method($action);
        $this->dispatch($url, 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/software/index/?filter=test');

        // Confirmed POST request should invoke action and redirect
        $this->_software = $this->getMock('Model_Software');
        $this->_software->expects($this->once())
                        ->method($action)
                        ->with($tmUtf16);
        $this->dispatch($url, 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/software/index/?filter=test');

        // Missing name should throw exception
        $this->setExpectedException('RuntimeException', 'Missing name parameter');
        $this->dispatch(substr($url, 0, strpos($url, '?')));
    }
}
