<?php

/**
 * Tests for AddToGroup form
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\AddToGroup;
use Model\Group\GroupManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for AddToGroup form
 */
class AddToGroupTest extends \Console\Test\AbstractFormTest
{
    /**
     * @var MockObject|GroupManager
     */
    protected $_groupManager;

    public function setUp(): void
    {
        $this->_groupManager = $this->createMock('Model\Group\GroupManager');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $groups = array(
            array('Name' => 'group1'),
            array('Name' => 'group2'),
        );
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
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
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $what);
        $this->assertCount(3, $what->getValueOptions());
        $this->assertEquals(\Model\Client\Client::MEMBERSHIP_AUTOMATIC, $what->getValue());
        $this->assertEquals(array('class' => 'what'), $what->getLabelAttributes());

        $where = $this->_form->get('Where');
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $where);
        $this->assertCount(2, $where->getValueOptions());
        $this->assertEquals('new', $where->getValue());
        $this->assertEquals(array('class' => 'where'), $where->getLabelAttributes());

        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('NewGroup'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('Description'));

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
            'NewGroup' => array('callbackValue' => "TRANSLATE(Value is required and can't be empty)")
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
            'NewGroup' => array('callbackValue' => "TRANSLATE(Value is required and can't be empty)")
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
            'NewGroup' => array('callbackValue' => 'TRANSLATE(The input is more than 255 characters long)')
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
            'NewGroup' => array('callbackValue' => 'TRANSLATE(The name already exists)')
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
            'Description' => array('callbackValue' => 'TRANSLATE(The input is more than 255 characters long)')
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

    public function testProcessNewGroup()
    {
        $group = $this->createMock('Model\Group\Group');
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
        $form = $this->createPartialMock(AddToGroup::class, ['getData']);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('GroupManager', $this->_groupManager);
        $this->assertEquals($group, $form->process('filter', 'search', 'operator', 'invert'));
    }

    public function testProcessExistingGroup()
    {
        $group = $this->createMock('Model\Group\Group');
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
        $form = $this->createPartialMock(AddToGroup::class, ['getData']);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('GroupManager', $this->_groupManager);
        $this->assertEquals($group, $form->process('filter', 'search', 'operator', 'invert'));
    }
}
