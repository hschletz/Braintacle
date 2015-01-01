<?php
/**
 * Tests for SoftwareController
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
     * @var \Console\Form\SoftwareFilter
     */
    protected $_form;

    /**
     * Sample result data
     * @var array[]
     */
    protected $_result = array(
        array('Name' => 'name1', 'RawName' => 'raw_name1', 'NumComputers' => 1),
        array('Name' => 'name2', 'RawName' => 'raw_name2', 'NumComputers' => 2),
    );

    /**
     * Session container
     * @var \Zend\Session\Container;
     */
    protected $_session;

    public function setUp()
    {
        $this->_software = $this->getMock('Model_Software');
        $this->_form = $this->getMock('Console\Form\SoftwareFilter');
        $this->_session = new \Zend\Session\Container('ManageSoftware');
        parent::setUp();
    }

    protected function _createController()
    {
        return new \Console\Controller\SoftwareController($this->_software, $this->_form);
    }

    public function testIndexActionDefaultFilterAccepted()
    {
        $filters = array(
            'Os' => 'windows',
            'Status' => 'accepted',
            'Unique' => null,
        );
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($this->_result));
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('accepted');
        $this->_form->expects($this->once())->method('render');
        unset($this->_session->filter);
        $this->dispatch('/console/software/index/');
        $this->assertEquals('accepted', $this->_session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertNotQueryContentContains(
            'td a',
            'Akzeptieren'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/software/ignore/?name=raw_name2"]',
            'Ignorieren'
        );
        $this->assertQueryContentContains(
            'td',
            "\nname2\n"
        );
        $this->assertQueryContentContains(
            'td[class="textright"] a[href*="/console/computer/index/"][href*="search=raw_name2"]',
            '2'
        );
    }

    public function testIndexActionFilterIgnored()
    {
        $filters = array(
            'Os' => 'windows',
            'Status' => 'ignored',
            'Unique' => null,
        );
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($this->_result));
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('ignored');
        $this->_form->expects($this->once())->method('render');
        $this->dispatch('/console/software/index/?filter=ignored');
        $this->assertEquals('ignored', $this->_session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'td a[href="/console/software/accept/?name=raw_name2"]',
            'Akzeptieren'
        );
        $this->assertNotQueryContentContains(
            'td a',
            'Ignorieren'
        );
    }

    public function testIndexActionFilterNew()
    {
        $filters = array(
            'Os' => 'windows',
            'Status' => 'new',
            'Unique' => null,
        );
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($this->_result));
        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('new');
        $this->_form->expects($this->once())->method('render');
        $this->dispatch('/console/software/index/?filter=new');
        $this->assertEquals('new', $this->_session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains(
            'td a[href="/console/software/accept/?name=raw_name2"]',
            'Akzeptieren'
        );
        $this->assertQueryContentContains(
            'td a[href="/console/software/ignore/?name=raw_name2"]',
            'Ignorieren'
        );
    }

    public function testIndexActionFilterAll()
    {
        $filters = array(
            'Os' => 'windows',
            'Status' => 'all',
            'Unique' => null,
        );
        $this->_software->expects($this->once())
                        ->method('find')
                        ->with(
                            array('Name', 'NumComputers'),
                            'Name',
                            'asc',
                            $filters
                        )
                        ->will($this->returnValue($this->_result));

        $this->_form->expects($this->once())
                    ->method('setFilter')
                    ->with('all');
        $this->_form->expects($this->once())->method('render');
        $this->dispatch('/console/software/index/?filter=all');
        $this->assertEquals('all', $this->_session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertNotQueryContentContains(
            'td a',
            'Akzeptieren'
        );
        $this->assertNotQueryContentContains(
            'td a',
            'Ignorieren'
        );
    }

    public function testAcceptActionGet()
    {
        $this->_testManageActionGet('accept');
    }

    public function testAcceptActionPostNo()
    {
        $this->_testManageActionPostNo('accept');
    }

    public function testAcceptActionPostYes()
    {
        $this->_testManageActionPostYes('accept');
    }

    public function testAcceptActionMissingName()
    {
        $this->_testManageActionMissingName('accept');
    }

    public function testIgnoreActionGet()
    {
        $this->_testManageActionGet('ignore');
    }

    public function testIgnoreActionPostNo()
    {
        $this->_testManageActionPostNo('ignore');
    }

    public function testIgnoreActionPostYes()
    {
        $this->_testManageActionPostYes('ignore');
    }

    public function testIgnoreActionMissingName()
    {
        $this->_testManageActionMissingName('ignore');
    }

    protected function _testManageActionGet($action)
    {
        $tmBad = chr(0xc2) . chr(0x99); // Incorrect representation of TM symbol, filtered where necessary
        $tmGood  = chr(0xe2) . chr(0x84) . chr(0xa2); // Corrected representation of TM symbol
        $this->dispatch("/console/software/$action/?name=" . urlencode($tmBad));
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
        $this->assertQueryContentRegex('p', "/$tmGood/");
    }

    protected function _testManageActionPostNo($action)
    {
        $this->_software->expects($this->never())
                        ->method($action);
        $session = new \Zend\Session\Container('ManageSoftware');
        $session->filter = 'test';
        $this->dispatch("/console/software/$action/?name=test", 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/software/index/?filter=test');
    }

    protected function _testManageActionPostYes($action)
    {
        $tmBad = chr(0xc2) . chr(0x99); // Incorrect representation of TM symbol
        $this->_software = $this->getMock('Model_Software');
        $this->_software->expects($this->once())
                        ->method($action)
                        ->with($tmBad);
        $session = new \Zend\Session\Container('ManageSoftware');
        $session->filter = 'test';
        $this->dispatch("/console/software/$action/?name=$tmBad", 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/software/index/?filter=test');
    }

    protected function _testManageActionMissingName($action)
    {
        $this->dispatch("/console/software/$action/");
        $this->assertApplicationException('RuntimeException', 'Missing name parameter');
    }
}
