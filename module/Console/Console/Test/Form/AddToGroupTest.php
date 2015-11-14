<?php
/**
 * Tests for AddToGroup form
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

namespace Console\Test\Form;

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for AddToGroup form
 */
class AddToGroupTest extends \Console\Test\AbstractFormTest
{
    /**
     * Group manager mock
     * @var \Model\Group\GroupManager
     */
    protected $_groupManager;

    public function setUp()
    {
        $this->_groupManager = $this->getMockBuilder('Model\Group\GroupManager')
                                    ->disableOriginalConstructor()
                                    ->getMock();
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $groups = array(
            array('Name' => 'group1'),
            array('Name' => 'group2'),
        );
        $resultSet = new \Zend\Db\ResultSet\ResultSet;
        $resultSet->initialize($groups);
        $this->_groupManager->expects($this->once())
                            ->method('getGroups')
                            ->with(null, null, 'Name')
                            ->willReturn($resultSet);
        $form = new \Console\Form\AddToGroup(
            null,
            array('GroupManager' => $this->_groupManager)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $what = $this->_form->get('What');
        $this->assertInstanceOf('Zend\Form\Element\Radio', $what);
        $this->assertCount(3, $what->getValueOptions());
        $this->assertEquals(\Model\Client\Client::MEMBERSHIP_AUTOMATIC, $what->getValue());

        $where = $this->_form->get('Where');
        $this->assertInstanceOf('Zend\Form\Element\Radio', $where);
        $this->assertCount(2, $where->getValueOptions());
        $this->assertEquals('new', $where->getValue());
        $this->assertEquals('selectElements()', $where->getAttribute('onchange'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('NewGroup'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('Description'));

        $existingGroup = $this->_form->get('ExistingGroup');
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $existingGroup);
        $this->assertEquals(
            array('group1', 'group2'),
            $existingGroup->getValueOptions()
        );

        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterNewGroupValid()
    {
        $max = str_repeat("\xC3\x84", 255);
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => " $max ",
            'Description' => " $max ",
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals($max, $data['NewGroup']);
        $this->assertEquals($max, $data['Description']);
    }

    public function testInputFilterNewGroupNameEmpty()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => '',
            'Description' => 'description',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewGroup' => array('callbackValue' => "Eingabe erforderlich")
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewGroupNameWhitespaceOnly()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => ' ',
            'Description' => 'description',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewGroup' => array('callbackValue' => "Eingabe erforderlich")
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewGroupNameTooLong()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => str_repeat('x', 256),
            'Description' => 'description',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewGroup' => array('callbackValue' => 'Die Eingabe ist länger als 255 Zeichen')
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewGroupNameExists()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => ' Group2 ',
            'Description' => 'description',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewGroup' => array('callbackValue' => 'Der Name existiert bereits')
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewGroupDescriptionEmpty()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => 'new_group',
            'Description' => '',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertNull($data['Description']);
    }

    public function testInputFilterNewGroupDescriptionWhitespaceOnly()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => 'new_group',
            'Description' => ' ',
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertNull($data['Description']);
    }

    public function testInputFilterNewGroupDescriptionTooLong()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'new',
            'NewGroup' => 'new_group',
            'Description' => str_repeat('x', 256),
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Description' => array('callbackValue' => 'Die Eingabe ist länger als 255 Zeichen')
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterExistingGroup()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'What' => 0,
            'Where' => 'existing',
            'NewGroup' => 'group1', // invalid but ignored
            'Description' => str_repeat('x', 256), // invalid but ignored
            'ExistingGroup' => 'group1',
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testRender()
    {
        $this->_form->get('NewGroup')->setMessages(array('message'));
        $view = $this->_createView();
        $html = $this->_form->render($view);
        $this->assertContains('function selectElements()', $view->headScript()->toString());
        $this->assertContains('selectElements()', $view->placeholder('BodyOnLoad')->getValue());
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('//legend//*[text()="What to save"]', $document));
        $this->assertCount(3, Query::execute('//input[@type="radio"][@name="What"]', $document));
        $this->assertCount(1, Query::execute('//legend//*[text()="Where to save"]', $document));
        $this->assertCount(2, Query::execute('//input[@type="radio"][@name="Where"]', $document));
        $this->assertCount(
            1,
            Query::execute(
                '//input[@type="text"][@name="NewGroup"][@class="input-error"]',
                $document
            )
        );
        $this->assertCount(1, Query::execute('//input[@type="text"][@name="Description"]', $document));
        $this->assertCount(1, Query::execute('//select[@name="ExistingGroup"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
        $this->assertCount(1, Query::execute('//ul[@class="error"]/li[text()="message"]', $document));
    }

    public function testProcessNewGroup()
    {
        $group = $this->getMock('Model\Group\Group');
        $group->expects($this->once())
              ->method('setMembersFromQuery')
              ->with('what', 'filter', 'search', 'operator', 'invert');
        $this->_groupManager->expects($this->once())
                            ->method('createGroup')
                            ->with('name', 'description');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('name')
                            ->willReturn($group);
        $data = array(
            'What' => 'what',
            'Where' => 'new',
            'NewGroup' => 'name',
            'Description' => 'description',
        );
        $form = $this->getMockBuilder('Console\Form\AddToGroup')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('GroupManager', $this->_groupManager);
        $this->assertEquals($group, $form->process('filter', 'search', 'operator', 'invert'));
    }

    public function testProcessExistingGroup()
    {
        $group = $this->getMock('Model\Group\Group');
        $group->expects($this->once())
              ->method('setMembersFromQuery')
              ->with('what', 'filter', 'search', 'operator', 'invert');
        $this->_groupManager->expects($this->never())->method('createGroup');
        $this->_groupManager->expects($this->once())
                            ->method('getGroup')
                            ->with('name')
                            ->willReturn($group);
        $data = array(
            'What' => 'what',
            'Where' => 'existing',
            'ExistingGroup' => 'name',
        );
        $form = $this->getMockBuilder('Console\Form\AddToGroup')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('GroupManager', $this->_groupManager);
        $this->assertEquals($group, $form->process('filter', 'search', 'operator', 'invert'));
    }
}
