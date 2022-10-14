<?php

/**
 * Tests for Search form
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

use Console\Form\Search;
use Laminas\I18n\Translator\TranslatorInterface;
use Model\Client\CustomFieldManager;
use Model\Registry\RegistryManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for Search form
 */
class SearchTest extends \Console\Test\AbstractFormTest
{
    /**
     * Translator mock object
     * @var MockObject|TranslatorInterface
     */
    protected $_translator;

    /**
     * RegistryManager mock object
     * @var MockObject|RegistryManager
     */
    protected $_registryManager;

    /**
     * CustomFieldManager mock object
     * @var MockObject|CustomFieldManager
     */
    protected $_customFieldManager;

    const OPERATORS_TEXT = [
        'like' => "TRANSLATE(Substring match, wildcards '?' and '*' allowed)",
        'eq' => 'TRANSLATE(Exact match)',
    ];

    const OPERATORS_ORDINAL = [
        'eq' => '=',
        'ne' => '!=',
        'lt' => '<',
        'le' => '<=',
        'ge' => '>=',
        'gt' => '>',
    ];

    public function setUp(): void
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(array(array('Name' => 'RegValue')));

        $this->_translator = $this->createMock('\Laminas\I18n\Translator\TranslatorInterface');
        $this->_translator->method('translate')->willReturnCallback([$this, 'translatorMock']);

        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_registryManager->expects($this->once())
                               ->method('getValueDefinitions')
                               ->willReturn($resultSet);

        $this->_customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $this->_customFieldManager->expects($this->once())
                            ->method('getFields')
                            ->will(
                                $this->returnValue(
                                    array(
                                        'TAG' => 'text',
                                        'Clob' => 'clob',
                                        'Integer' => 'integer',
                                        'Float' => 'float',
                                        'Date' => 'date',
                                    )
                                )
                            );
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => $this->_translator,
                'registryManager' => $this->_registryManager,
                'customFieldManager' => $this->_customFieldManager,
            )
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $filter = $this->_form->get('filter');
        $this->assertInstanceOf('Laminas\Form\Element\Select', $filter);
        $filters = $filter->getValueOptions();
        $this->assertContains('TRANSLATE(Software: Name)', $filters); // Hardcoded
        $this->assertContains('Registry: RegValue', $filters); // dynamically added
        $this->assertContains('TRANSLATE(User defined: TRANSLATE(Category))', $filters); // dynamically added; renamed
        $this->assertContains('TRANSLATE(User defined: Integer)', $filters); // dynamically added
        $this->assertEquals('Name', $filter->getValue());
        $this->assertEquals(
            [
                'CpuClock' => 'integer',
                'CpuCores' => 'integer',
                'InventoryDate' => 'date',
                'LastContactDate' => 'date',
                'PhysicalMemory' => 'integer',
                'SwapMemory' => 'integer',
                'Filesystem.Size' => 'integer',
                'Filesystem.FreeSpace' => 'integer',
                'CustomFields.Integer' => 'integer',
                'CustomFields.Float' => 'float',
                'CustomFields.Date' => 'date',
            ],
            json_decode($filter->getAttribute('data-types'), true)
        );

        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('search'));

        $operator = $this->_form->get('operator');
        $this->assertInstanceOf('Laminas\Form\Element\Select', $operator);
        $this->assertEquals('select_untranslated', $operator->getAttribute('type'));
        $this->assertEquals(self::OPERATORS_TEXT, $operator->getValueOptions());
        $this->assertEquals(json_encode(self::OPERATORS_TEXT), $operator->getAttribute('data-operators-text'));
        $this->assertEquals(
            json_encode(self::OPERATORS_ORDINAL),
            $operator->getAttribute('data-operators-ordinal')
        );

        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $this->_form->get('invert'));
    }

    public function testInitInvalidDatatype()
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize(new \EmptyIterator());
        $registryManager = $this->createMock('Model\Registry\RegistryManager');
        $registryManager->method('getValueDefinitions')->willReturn($resultSet);
        $customFieldManager = $this->createMock('Model\Client\CustomFieldManager');
        $customFieldManager->expects($this->once())->method('getFields')->willReturn(array('test' => 'invalid'));
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => new \Laminas\Mvc\I18n\Translator(new \Laminas\Mvc\I18n\DummyTranslator()),
                'registryManager' => $registryManager,
                'customFieldManager' => $customFieldManager,
            )
        );
        $this->expectException('UnexpectedValueException');
        $form->init();
    }

    public function testSetDataText()
    {
        $data = array(
            'filter' => 'CustomFields.TAG',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
        $this->assertEquals(self::OPERATORS_TEXT, $this->_form->get('operator')->getValueOptions());
    }

    public function testSetDataClob()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
        $this->assertEquals(self::OPERATORS_TEXT, $this->_form->get('operator')->getValueOptions());
    }

    public function testSetDataInteger()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => '1234',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.234', $this->_form->get('search')->getValue());

        $data['search'] = '1.234';
        $this->_form->setData($data);
        $this->assertEquals('1.234', $this->_form->get('search')->getValue());

        $data['search'] = '1,234';
        $this->_form->setData($data);
        $this->assertEquals('1,234', $this->_form->get('search')->getValue());

        $this->assertEquals(self::OPERATORS_ORDINAL, $this->_form->get('operator')->getValueOptions());
    }

    public function testSetDataFloat()
    {
        $data = array(
            'filter' => 'CustomFields.Float',
            'search' => '1234.5678',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.234,5678', $this->_form->get('search')->getValue());

        $data['search'] = '1.234,5678';
        $this->_form->setData($data);
        $this->assertEquals('1.234,5678', $this->_form->get('search')->getValue());

        $data['search'] = '1,234.5678';
        $this->_form->setData($data);
        $this->assertEquals('1,234.5678', $this->_form->get('search')->getValue());

        $this->assertEquals(self::OPERATORS_ORDINAL, $this->_form->get('operator')->getValueOptions());
    }

    public function testSetDataDate()
    {
        $data = array(
            'filter' => 'CustomFields.Date',
            'search' => new \DateTime('2014-05-01'),
        );
        $this->_form->setData($data);
        $this->assertEquals('01.05.2014', $this->_form->get('search')->getValue());

        $data['search'] = '2014-05-01';
        $this->_form->setData($data);
        $this->assertEquals('01.05.2014', $this->_form->get('search')->getValue());

        $data['search'] = '05/01/2014';
        $this->_form->setData($data);
        $this->assertEquals('05/01/2014', $this->_form->get('search')->getValue());

        $this->assertEquals(self::OPERATORS_ORDINAL, $this->_form->get('operator')->getValueOptions());
    }

    public function testSetDataNoSearch()
    {
        $data = array(
            'filter' => 'CustomFields.TAG',
        );
        $this->_form->setData($data); // Must not generate error
    }

    public function testInputFilterText()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => ' test ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(' test ', $this->_form->getData()['search']);

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInteger()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => ' 1234 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['search']);

        $data['search'] = '1.234';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['search']);

        $data['search'] = '1,234';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterFloat()
    {
        $data = array(
            'filter' => 'CustomFields.Float',
            'search' => ' 1234,5678 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234.5678, $this->_form->getData()['search']);

        $data['search'] = '1.234,5678';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234.5678, $this->_form->getData()['search']);

        $data['search'] = '1,234.5678';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterDate()
    {
        $data = array(
            'filter' => 'CustomFields.Date',
            'search' => ' 1.5.2014 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('2014-05-01', $this->_form->getData()['search']->format('Y-m-d'));

        $data['search'] = '2014-05-01';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('2014-05-01', $this->_form->getData()['search']->format('Y-m-d'));

        $data['search'] = '05/01/2014';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        $data['search'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnTextOperator()
    {
        $data = array(
            'filter' => 'CustomFields.Clob',
            'search' => '1',
            'operator' => 'like',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'ne';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnOrdinalOperator()
    {
        $data = array(
            'filter' => 'CustomFields.Integer',
            'search' => '1',
            'operator' => 'ne',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'like';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInvalidFilter()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid filter: invalidFilter');
        $this->_form->setData(array('filter' => 'invalidFilter'));
    }

    public function testMissingFilterOnSearchValidation()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No filter submitted');

        $form = new Search();
        $form->validateSearch('value', ['search' => 'value']);
    }

    public function testMissingFilterOnOperatorValidation()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No filter submitted');

        $form = new Search();
        $form->validateOperator('value', ['search' => 'value']);
    }
}
