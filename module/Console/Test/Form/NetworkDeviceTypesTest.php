<?php

/**
 * Tests for NetworkDeviceTypes form
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Test\DomMatcherTrait;
use Console\Form\NetworkDeviceTypes;
use Console\Test\AbstractFormTestCase;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\View\Helper\FormElementErrors;
use Model\Network\DeviceManager;
use PHPUnit\Framework\MockObject\MockObject;

// phpcs:disable Generic.Files.LineLength.TooLong

/**
 * Tests for NetworkDeviceTypes form
 */
class NetworkDeviceTypesTest extends AbstractFormTestCase
{
    use DomMatcherTrait;

    /**
     * DeviceManager mock
     * @var MockObject|DeviceManager
     */
    protected $_deviceManager;

    public function setUp(): void
    {
        $this->_deviceManager = $this->createMock('Model\Network\DeviceManager');
        $this->_deviceManager->expects($this->once())
            ->method('getTypeCounts')
            ->willReturn(array('name0' => 0, 'name1' => 1));
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\NetworkDeviceTypes(
            null,
            array('DeviceManager' => $this->_deviceManager)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('Add'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));

        /** @var FieldsetInterface */
        $types = $this->_form->get('Types');
        $this->assertCount(2, $types);

        $element = $types->get('name0');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('name0', $element->getValue());

        $element = $types->get('name1');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('name1', $element->getValue());
    }

    public function testInputFilterUnchangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name0', $data['Types']['name0']);
        $this->assertEquals('name1', $data['Types']['name1']);
    }

    public function testInputFilterChangedNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' NAME0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('NAME0', $data['Types']['name0']);
        $this->assertEquals('name1', $data['Types']['name1']);
    }

    public function testInputFilterDuplicateNewNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' name_new ',
                'name1' => 'name_NEW',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
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
            'Add' => '',
            'Types' => array(
                'name0' => 'name0',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterNewNameConflictsWithRenamedNameNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => 'name_new',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
                'name1' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
            ),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterSwapNamesNoAdd()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => ' NAME1 ',
                'name1' => ' NAME0 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Types' => array(
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
            'Add' => ' ',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('', $data['Add']);
    }

    public function testInputFilterUnchangedAddValid()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' name2 ',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name2', $data['Add']);
    }

    public function testInputFilterUnchangedAddExistingName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' NAME0',
            'Types' => array(
                'name0' => ' name0 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Add' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterChangedAddExistingNewName()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => ' NAME2',
            'Types' => array(
                'name0' => ' name2 ',
                'name1' => ' name1 ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = array(
            'Add' => array('callbackValue' => 'TRANSLATE(The name already exists)'),
        );
        $this->assertEquals($messages, $this->_form->getMessages());
    }

    public function testInputFilterRenamedEmpty()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => '',
            'Types' => array(
                'name0' => '',
                'name1' => ' ',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(2, $messages['Types']);
        $this->assertCount(1, $messages['Types']['name0']);
        $this->assertCount(1, $messages['Types']['name1']);
        $this->assertArrayHasKey('isEmpty', $messages['Types']['name0']);
        $this->assertArrayHasKey('isEmpty', $messages['Types']['name1']);
    }

    public function testInputFilterStringLengthMax()
    {
        $data = array(
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'Add' => str_repeat("\xC3\x84", 255),
            'Types' => array(
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
            'Add' => str_repeat("\xC3\x84", 256),
            'Types' => array(
                'name0' => str_repeat("\xC3\x96", 256),
                'name1' => 'name1',
            ),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(2, $messages);
        $this->assertCount(1, $messages['Types']['name0']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['Types']['name0']);
        $this->assertCount(1, $messages['Add']);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['Add']);
    }

    public function testRenderFieldsetNoMessages()
    {
        $html = $this->_form->renderFieldset($this->createView(), $this->_form);
        $xPath = $this->createXpath($html);
        $this->assertXpathCount(1, $xPath, '//div[@class="table"]');
        $this->assertXpathCount(
            1,
            $xPath,
            '//input[@name="name0"]/following-sibling::a[@href="/console/preferences/deletedevicetype/?name=name0"][text()="Löschen"]',
        );
        $this->assertXpathCount(1, $xPath, '//input[@name="name1"]');
        $this->assertXpathCount(1, $xPath, '//input[@name="Add"]');
        $this->assertXpathCount(1, $xPath, '//a');
        $this->assertXpathCount(1, $xPath, '//input[@type="submit"]');
        $this->assertXpathCount(0, $xPath, '//input[@class="input-error"]');
        $this->assertXpathCount(0, $xPath, '//ul');
    }

    public function testRenderFieldsetMessages()
    {
        $this->_form->get('Types')->get('name0')->setMessages(array('message_name0'));
        $this->_form->get('Add')->setMessages(array('message_add'));

        /** @ver MockObject|FormElementErrors */
        $formElementErrors = $this->createMock(FormElementErrors::class);
        $formElementErrors->method('__invoke')
            ->with($this->isInstanceOf('Laminas\Form\ElementInterface'), array('class' => 'error'))
            ->willReturnCallback(array($this, 'formElementErrorsMock'));

        $view = $this->createView();
        $view->getHelperPluginManager()->setService('formElementErrors', $formElementErrors);

        $html = $this->_form->renderFieldset($view, $this->_form);
        $xPath = $this->createXpath($html);
        $this->assertXpathCount(1, $xPath, '//input[@name="name0"]/following::ul[1][@class="errorMock"]/li[text()="message_name0"]');
        $this->assertXpathCount(1, $xPath, '//input[@name="Add"]/following::ul[1][@class="errorMock"]/li[text()="message_add"]');
        $this->assertXpathCount(2, $xPath, '//input[@class="input-error"]');
        $this->assertXpathCount(2, $xPath, '//ul');
    }

    public function testProcessRenameNoAdd()
    {
        $deviceManager = $this->createMock('Model\Network\DeviceManager');
        $deviceManager->expects($this->never())->method('addType');
        $deviceManager->expects($this->once())->method('renameType')->with('name1', 'new_name');
        $data = array(
            'Add' => '',
            'Types' => array(
                'name0' => 'name0',
                'name1' => 'new_name',
            ),
        );
        $form = $this->createPartialMock(NetworkDeviceTypes::class, ['getData']);
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $form->setOption('DeviceManager', $deviceManager);

        $form->process();
    }

    public function testProcessAdd()
    {
        $deviceManager = $this->createMock('Model\Network\DeviceManager');
        $deviceManager->expects($this->once())->method('addType')->with('new_name');
        $deviceManager->expects($this->never())->method('renameType');
        $data = array(
            'Add' => 'new_name',
            'Types' => array(
                'name0' => 'name0',
                'name1' => 'name1',
            ),
        );
        $form = $this->createPartialMock(NetworkDeviceTypes::class, ['getData']);
        $form->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $form->setOption('DeviceManager', $deviceManager);

        $form->process();
    }
}
