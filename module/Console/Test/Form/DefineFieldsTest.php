<?php

/**
 * Tests for DefineFields form
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

use Console\Form\DefineFields;
use Laminas\Dom\Document\Query as Query;
use Laminas\Form\View\Helper\FormElementErrors;
use Model\Client\CustomFieldManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for DefineFields form
 */
class DefineFieldsTest extends \Console\Test\AbstractFormTest
{
    /**
     * CustomFieldManager mock object
     * @var MockObject|CustomFieldManager
     */
    protected $_customFieldManager;

    public function setUp(): void
    {
        $fields = array(
            'TAG' => 'text', // should be ignored
            'name0' => 'text',
            'name1' => 'integer',
        );
        $this->_customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $this->_customFieldManager->expects($this->once())->method('getFields')->willReturn($fields);
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\DefineFields(
            null,
            array('CustomFieldManager' => $this->_customFieldManager)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('NewName'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
        $this->assertInstanceOf('Laminas\Form\Element\Select', $this->_form->get('NewType'));

        $fields = $this->_form->get('Fields');
        $this->assertCount(2, $fields);

        $element = $fields->get('name0');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('name0', $element->getValue());

        $element = $fields->get('name1');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
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
                'name0' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
                'name0' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
            'NewName' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
            'NewName' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
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
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(2, $messages['Fields']);
        $this->assertCount(1, $messages['Fields']['name0']);
        $this->assertCount(1, $messages['Fields']['name1']);
        $this->assertArrayHasKey('isEmpty', $messages['Fields']['name0']);
        $this->assertArrayHasKey('isEmpty', $messages['Fields']['name1']);
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
        $messages = $this->_form->getMessages();
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['Fields']['name0']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['Fields']['name0']);
        $this->assertCount(1, $messages['NewName']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['NewName']);
    }

    public function testSelectOptionsTranslated()
    {
        $view = $this->createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(5, Query::execute('//select[@name="NewType"]/option', $document));
        $this->assertCount(
            1,
            Query::execute('//select[@name="NewType"]/option[@value="text"][text()="Text"]', $document)
        );
        $this->assertCount(
            1,
            Query::execute('//select[@name="NewType"]/option[@value="clob"][text()="Langer Text"]', $document)
        );
        $this->assertCount(
            1,
            Query::execute('//select[@name="NewType"]/option[@value="integer"][text()="Ganzzahl"]', $document)
        );
        $this->assertCount(
            1,
            Query::execute('//select[@name="NewType"]/option[@value="float"][text()="Kommazahl"]', $document)
        );
        $this->assertCount(
            1,
            Query::execute('//select[@name="NewType"]/option[@value="date"][text()="Datum"]', $document)
        );
    }

    public function testRenderFieldsetNoMessages()
    {
        $html = $this->_form->renderFieldset($this->createView(), $this->_form);
        $document = new \Laminas\Dom\Document(static::HTML_HEADER . $html);
        $this->assertCount(1, Query::execute('//div[@class="table"]', $document));
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
                '[@href="/console/preferences/deletefield/?name=name0"][text()="Löschen"]',
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

        /** @var MockObject|FormElementErrors */
        $formElementErrors = $this->createMock(FormElementErrors::class);
        $formElementErrors->method('__invoke')
                          ->with($this->isInstanceOf('Laminas\Form\ElementInterface'), array('class' => 'error'))
                          ->willReturnCallback(array($this, 'formElementErrorsMock'));

        $view = $this->createView();
        $view->getHelperPluginManager()->setService('formElementErrors', $formElementErrors);

        $html = $this->_form->renderFieldset($view, $this->_form);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="name0"]/following::ul[1][@class="errorMock"]/li[text()="message_name0"]',
                $document
            )
        );
        $this->assertCount(
            1,
            Query::execute(
                '//input[@name="NewName"]/following::ul[1][@class="errorMock"]/li[text()="message_add"]',
                $document
            )
        );
        $this->assertCount(2, Query::execute('//input[@class="input-error"]', $document));
        $this->assertCount(2, Query::execute('//ul', $document));
    }

    public function testProcessRenameNoAdd()
    {
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->never())->method('addField');
        $customFieldManager->expects($this->once())->method('renameField')->with('old_name', 'new_name');
        $data = array(
            'NewName' => '',
            'Fields' => array(
                'old_name' => 'new_name',
            ),
        );
        $form = $this->createPartialMock(DefineFields::class, ['getData']);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('CustomFieldManager', $customFieldManager);
        $form->process();
    }

    public function testProcessAdd()
    {
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('addField')->with('new_name', 'text');
        $customFieldManager->expects($this->never())->method('renameField');
        $data = array(
            'NewName' => 'new_name',
            'NewType' => 'text',
            'Fields' => array(),
        );
        $form = $this->createPartialMock(DefineFields::class, ['getData']);
        $form->expects($this->once())
             ->method('getData')
             ->will($this->returnValue($data));
        $form->setOption('CustomFieldManager', $customFieldManager);
        $form->process();
    }
}
