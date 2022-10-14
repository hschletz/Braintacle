<?php

/**
 * Tests for CustomFields form
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

use Laminas\Dom\Document\Query as Query;
use Model\Client\CustomFieldManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for CustomFields form
 */
class CustomFieldsTest extends \Console\Test\AbstractFormTest
{
    /**
     * CustomFields mock object
     * @var MockObject|CustomFieldManager
     */
    protected $_customFieldManager;

    public function setUp(): void
    {
        $this->_customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $this->_customFieldManager->expects($this->once())
                                  ->method('getFields')
                                  ->willReturn(
                                      array(
                                        'TAG' => 'text',
                                        'Clob' => 'clob',
                                        'Integer' => 'integer',
                                        'Float' => 'float',
                                        'Date' => 'date',
                                      )
                                  );
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\CustomFields(
            null,
            array('customFieldManager' => $this->_customFieldManager)
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));

        $fields = $this->_form->get('Fields');

        $element = $fields->get('TAG');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('Category', $element->getLabel());

        $element = $fields->get('Clob');
        $this->assertInstanceOf('Laminas\Form\Element\Textarea', $element);
        $this->assertEquals('Clob', $element->getLabel());

        $element = $fields->get('Integer');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('Integer', $element->getLabel());

        $element = $fields->get('Float');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('Float', $element->getLabel());

        $element = $fields->get('Date');
        $this->assertInstanceOf('Laminas\Form\Element\Text', $element);
        $this->assertEquals('Date', $element->getLabel());
    }

    public function testSetData()
    {
        $this->_form->setData(array('Fields' => array('Float' => '1234.5678')));
        $this->assertEquals('1.234,5678', $this->_form->get('Fields')->get('Float')->getValue());
    }

    public function testInputFilterTextEmpty()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('TAG' => ' ')));
        $this->assertTrue($this->_form->isValid());
        $this->assertNull($this->_form->getData()['Fields']['TAG']);
    }

    public function testInputFilterTextTrim()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('TAG' => ' trim ')));
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('trim', $this->_form->getData()['Fields']['TAG']);
    }

    public function testInputFilterTextMax()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('TAG' => str_repeat("\xC3\x84", 255))));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterTextTooLong()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('TAG' => str_repeat("\xC3\x84", 256))));
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterClobLength()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Clob' => str_repeat("\xC3\x84", 256))));
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterClobTrim()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Clob' => ' trim ')));
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('trim', $this->_form->getData()['Fields']['Clob']);
    }

    public function testInputFilterNormalizeInvalid()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Integer' => '1a')));
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterNormalizeValid()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Integer' => ' 1.000 ')));
        $this->assertTrue($this->_form->isValid());
        $value = $this->_form->getData()['Fields']['Integer'];
        $this->assertIsInt($value);
        $this->assertEquals(1000, $value);
    }

    public function testInputFilterNormalizeZero()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Integer' => ' 0 ')));
        $this->assertTrue($this->_form->isValid());
        $value = $this->_form->getData()['Fields']['Integer'];
        $this->assertIsInt($value);
        $this->assertEquals(0, $value);
    }

    public function testInputFilterNormalizeEmpty()
    {
        $this->_form->setValidationGroup(['Fields']);
        $this->_form->setData(array('Fields' => array('Integer' => ' ')));
        $this->assertTrue($this->_form->isValid());
        $this->assertNull($this->_form->getData()['Fields']['Integer']);
    }

    public function testRenderFieldset()
    {
        $html = $this->_form->renderFieldset($this->createView(), $this->_form);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(4, Query::execute('//input[@type="text"]', $document));
        $this->assertCount(1, Query::execute('//textarea', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
        // Check for manual translation
        $this->assertCount(1, Query::execute('//label/span[text()="Kategorie"]', $document));
    }
}
