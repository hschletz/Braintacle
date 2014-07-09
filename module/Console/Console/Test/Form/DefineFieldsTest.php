<?php
/**
 * Tests for DefineFields form
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

namespace Console\Test\Form;

use \Zend\Dom\Document\Query as Query;

/**
 * Tests for DefineFields form
 */
class DefineFieldsTest extends \Console\Test\AbstractFormTest
{
    /**
     * CustomFields mock object
     * @var \Model_UserDefinedInfo
     */
    protected $_fields;

    public function setUp()
    {
        $fields = array(
            'TAG' => 'text', // should be ignored
            'name0' => 'text',
            'name1' => 'integer',
        );
        $this->_fields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $this->_fields->expects($this->once())
                      ->method('getPropertyTypes')
                      ->will($this->returnValue($fields));
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\DefineFields(
            null,
            array('CustomFieldsModel' => $this->_fields)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('NewName'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));

        $newType = $this->_form->get('NewType');
        $this->assertInstanceOf('Zend\Form\Element\Select', $newType);
        $this->assertEquals(
            array(
                'text' => 'Text',
                'clob' => 'Langer Text',
                'integer' => 'Ganzzahl',
                'float' => 'Kommazahl',
                'date' => 'Datum',
            ),
            $newType->getValueOptions()
        );

        $fields = $this->_form->get('Fields');
        $this->assertCount(2, $fields);

        $element = $fields->get('name0');
        $this->assertInstanceOf('Zend\Form\Element\Text', $element);
        $this->assertEquals('name0', $element->getValue());

        $element = $fields->get('name1');
        $this->assertInstanceOf('Zend\Form\Element\Text', $element);
        $this->assertEquals('name1', $element->getValue());
    }

    public function testInputFilterUnchangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name0', $data['Fields']['name0']);
        $this->assertEquals('name1', $data['Fields']['name1']);
    }

    public function testInputFilterChangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' NAME0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('NAME0', $data['Fields']['name0']);
        $this->assertEquals('name1', $data['Fields']['name1']);
    }

    public function testInputFilterDuplicateNewNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name_new ',
                'name1' => 'name_NEW',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name0' => array('callbackValue' => 'The name already exists'),
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewNameConflictsWithExistingNameNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => 'name0',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewNameConflictsWithRenamedNameNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => 'name_new',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterSwapNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' NAME1 ',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name0' => array('callbackValue' => 'The name already exists'),
                'name1' => array('callbackValue' => 'The name already exists'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterUnchangedAddWhitespaceOnly()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => ' ',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('', $data['NewName']);
    }

    public function testInputFilterUnchangedAddValid()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => ' name2 ',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name2', $data['NewName']);
    }

    public function testInputFilterUnchangedAddExistingName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => ' NAME0',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewName' => array('callbackValue' => 'The name already exists'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterChangedAddExistingNewName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => ' NAME2',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => ' name2 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'NewName' => array('callbackValue' => 'The name already exists'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterRenamedEmpty()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => '',
            'NewType' => 'text',
            'Fields' => array(
                'name0' => '',
                'name1' => ' ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name0' => array('isEmpty' => "Value is required and can't be empty"),
                'name1' => array('isEmpty' => "Value is required and can't be empty"),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterStringLengthMax()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => str_repeat("\xC3\x84", 255),
            'NewType' => 'text',
            'Fields' => array(
                'name0' => str_repeat("\xC3\x96", 255),
                'name1' => 'name1',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterStringLengthTooLong()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'NewName' => str_repeat("\xC3\x84", 256),
            'NewType' => 'text',
            'Fields' => array(
                'name0' => str_repeat("\xC3\x96", 256),
                'name1' => 'name1',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Fields' => array(
                'name0' => array('stringLengthTooLong' => 'The input is more than 255 characters long'),
            ),
            'NewName' => array('stringLengthTooLong' => 'The input is more than 255 characters long'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testRenderFieldsetNoMessages()
    {
        $html = $this->_form->renderFieldset($this->_createView(), $this->_form);
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(1, Query::execute('//div[@class="table"]', $document));
//         var_dump($html);
        $this->assertCount(
            1,
            Query::execute(
                "//input[@name='name0']/following-sibling::span[1][text()='\nText\n']",
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                "//input[@name='name1']/following-sibling::span[1][text()='\nGanzzahl\n']",
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="name0"]/following-sibling::span[2]/a' .
                '[@href="/console/preferences/deletefield/?name=name0"][text()="Delete"]',
                $document
            )
        );
        $this->assertCount(1, Query::execute('//input[@name="name1"]', $document));
        $this->assertCount(1, Query::execute('//input[@name="NewName"]', $document));
        $this->assertCount(1, Query::execute('//select[@name="NewType"]', $document));
        $this->assertCount(2, Query::execute('//a', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
        $this->assertCount(0, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(0, Query::execute('//ul', $document));
    }

    public function testRenderFieldsetMessages()
    {
        $this->_form->get('Fields')->get('name0')->setMessages(array('message_name0'));
        $this->_form->get('NewName')->setMessages(array('message_add'));
        $html = $this->_form->renderFieldset($this->_createView(), $this->_form);
        $document = new \Zend\Dom\Document($html);
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="name0"]/following::ul[1][@class="error"]/li[text()="message_name0"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="NewName"]/following::ul[1][@class="error"]/li[text()="message_add"]',
                $document
            )
        );
        $this->assertCount(2, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(2, Query::execute('//ul', $document));
    }

    public function testProcessRenameNoAdd()
    {
        $fields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $fields->expects($this->once())
               ->method('getPropertyTypes')
               ->will($this->returnValue($fields));
        $fields->expects($this->never())
               ->method('add');
        $fields->expects($this->once())
               ->method('rename')
               ->with('old_name', 'new_name');
        $data = array(
            'NewName' => '',
            'Fields' => array(
                'old_name' => 'new_name',
            ),
        );
        $form = $this->getMockBuilder('Console\Form\DefineFields')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('CustomFieldsModel', $fields);
        $form->init();
        $form->process();
    }

    public function testProcessAdd()
    {
        $fields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $fields->expects($this->once())
               ->method('getPropertyTypes')
               ->will($this->returnValue($fields));
        $fields->expects($this->once())
               ->method('add')
               ->with('new_name', 'text');
        $fields->expects($this->never())
               ->method('rename');
        $data = array(
            'NewName' => 'new_name',
            'NewType' => 'text',
            'Fields' => array(),
        );
        $form = $this->getMockBuilder('Console\Form\DefineFields')->setMethods(array('getData'))->getMock();
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('CustomFieldsModel', $fields);
        $form->init();
        $form->process();
    }
}
