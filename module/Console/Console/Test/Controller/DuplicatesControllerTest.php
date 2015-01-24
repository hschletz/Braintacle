<?php
/**
 * Tests for DuplicatesController
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
 * Tests for DuplicatesController
 */
class DuplicatesControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Duplicates mock
     * @var \Model\Client\DuplicatesManager
     */
    protected $_duplicates;

    /**
     * ShowDuplicates mock
     * @var \Console\Form\ShowDuplicates
     */
    protected $_showDuplicates;

    public function setUp()
    {
        $this->_duplicates = $this->getMockBuilder('Model\Client\DuplicatesManager')
                                  ->disableOriginalconstructor()
                                  ->getMock();
        $this->_showDuplicates = $this->getMock('Console\Form\ShowDuplicates');
        parent::setUp();
    }

    protected function _createController()
    {
        return new \Console\Controller\DuplicatesController($this->_duplicates, $this->_showDuplicates);
    }

    public function testService()
    {
        $this->_overrideService('Model\Client\DuplicatesManager', $this->_duplicates);
        $this->_overrideService('Console\Form\ShowDuplicates', $this->_showDuplicates, 'FormElementManager');
        parent::testService();
    }

    public function testIndexActionNoDuplicates()
    {
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('p', 'Keine Duplikate vorhanden.');
    }

    public function testIndexActionShowDuplicates()
    {
        $this->_duplicates->expects($this->exactly(4))
                          ->method('count')
                          ->will($this->returnValue(2));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        // List with 4 hyperlinks.
        $this->assertQueryCount('td a[href*="/console/duplicates/manage/?criteria="]', 4);
        $this->assertQueryContentContains('td a[href*="/console/duplicates/manage/?criteria="]', "\n2\n");
    }

    public function testIndexActionNoFlashMessages()
    {
        $this->_duplicates->method('count')
                          ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertNotXpathQuery('//ul');
    }

    public function testIndexActionRenderFlashMessages()
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addInfoMessage(
            'At least 2 different computers have to be selected'
        );
        $flashMessenger->addSuccessMessage(
            array("'%s' is no longer considered duplicate." => 'abc')
        );
        $this->_duplicates->method('count')
                          ->will($this->returnValue(0));
        $this->dispatch('/console/duplicates/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount('//ul', 2);
        $this->assertXPathQueryContentContains(
            '//ul[@class="info"]/li',
            'Es müssen mindestens 2 verschiedene Computer ausgewählt werden'
        );
        $this->assertXPathQueryContentContains(
            '//ul[@class="success"]/li',
            "'abc' wird nicht mehr als Duplikat betrachtet."
        );
    }

    public function testManageActionMissingCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->never())->method('render');
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with(null)
                          ->will($this->throwException(new \InvalidArgumentException('Invalid criteria')));
        $this->_duplicates->expects($this->never())->method('merge');
        $this->dispatch('/console/duplicates/manage/');
        $this->assertApplicationException('InvalidArgumentException');
    }

    public function testManageActionInvalidCriteria()
    {
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->never())->method('render');
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('invalid')
                          ->will($this->throwException(new \InvalidArgumentException('Invalid criteria')));
        $this->_duplicates->expects($this->never())->method('merge');
        $this->dispatch('/console/duplicates/manage/?criteria=invalid');
        $this->assertApplicationException('InvalidArgumentException');
    }

    public function testManageActionGet()
    {
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc')
                          ->willReturn('client_list');
        $this->_duplicates->expects($this->never())->method('merge');
        $this->_showDuplicates->expects($this->never())->method('setData');
        $this->_showDuplicates->expects($this->never())->method('isValid');
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())
                              ->method('setOptions')
                              ->with(array('computers' => 'client_list', 'order' => 'Id', 'direction' => 'asc'));
        $this->_showDuplicates->expects($this->once())->method('getMessages')->willReturn(array());
        $this->_showDuplicates->expects($this->once())->method('render')->willReturn('<form></form>');
        $this->dispatch('/console/duplicates/manage/?criteria=Name');
        $this->assertResponseStatusCode(200);
    }

    public function testManageActionPostValid()
    {
        $params = array(
            'computers' => array(1, 2),
            'mergeCustomFields' => '1',
            'mergeGroups' => '1',
            'mergePackages' => '0'
        );
        $this->_showDuplicates->expects($this->once())
                              ->method('setData')
                              ->with($params);
        $this->_showDuplicates->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(true));
        $this->_showDuplicates->expects($this->once())
                              ->method('getData')
                              ->will($this->returnValue($params));
        $this->_showDuplicates->expects($this->never())->method('getMessages');
        $this->_showDuplicates->expects($this->never())->method('render');
        $this->_duplicates->expects($this->never())->method('find');
        $this->_duplicates->expects($this->once())
                          ->method('merge')
                          ->with(array(1, 2), true, true, '0');
        $this->dispatch('/console/duplicates/manage/', 'POST', $params);
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            'The selected computers have been merged.',
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }

    public function testManageActionPostInvalid()
    {
        $this->_showDuplicates->expects($this->once())
                              ->method('setData');
        $this->_showDuplicates->expects($this->once())
                              ->method('isValid')
                              ->will($this->returnValue(false));
        $this->_showDuplicates->expects($this->never())->method('getData');
        $this->_showDuplicates->expects($this->once())
                              ->method('setOptions')
                              ->with(array('computers' => 'client_list', 'order' => 'Id', 'direction' => 'asc'));
        $this->_showDuplicates->expects($this->once())
                              ->method('getMessages')
                              ->willReturn(array('computers' => array('invalid')));
        $this->_showDuplicates->expects($this->once())
                              ->method('render')
                              ->willReturn('<form></form>');
        $this->_duplicates->expects($this->once())
                          ->method('find')
                          ->with('Name', 'Id', 'asc')
                          ->willReturn('client_list');
        $this->_duplicates->expects($this->never())
                          ->method('merge');
        $this->dispatch('/console/duplicates/manage/?criteria=Name', 'POST');
        $this->assertResponseStatusCode(200);
        $this->assertXPathQueryCount('//ul', 1);
        $this->assertXPathQueryCount('//li', 1);
        $this->assertXPathQueryContentContains('//ul[@class="error"]/li', 'invalid');
        $this->assertXpathQuery('//form');
    }

    public function testAllowActionGet()
    {
        $this->_duplicates->expects($this->never())
                          ->method('allow');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678');
        $this->assertResponseStatusCode(200);
        $this->assertQuery('form');
    }

    public function testAllowActionPostNo()
    {
        $this->_duplicates->expects($this->never())
                          ->method('allow');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/duplicates/show/?criteria=Serial');
    }

    public function testAllowActionPostYes()
    {
        $this->_duplicates->expects($this->once())
                          ->method('allow')
                          ->with('Serial', '12345678');
        $this->dispatch('/console/duplicates/allow/?criteria=Serial&value=12345678', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('/console/duplicates/index/');
        $this->assertContains(
            array("'%s' is no longer considered duplicate." => '12345678'),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentSuccessMessages()
        );
    }
}
