<?php
/**
 * Tests for Search form
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
 * Tests for Search form
 */
class SearchTest extends \Console\Test\AbstractFormTest
{
    /**
     * RegistryValue mock object
     * @var \Model_RegistryValue
     */
    protected $_registryValue;

    /**
     * CustonFields mock object
     * @var \Model_UserDefinedInfo
     */
    protected $_customFields;

    public function setUp()
    {
        $this->_registryValue = $this->getMock('Model_RegistryValue');
        $this->_registryValue->expects($this->once())
                             ->method('fetchAll')
                             ->will($this->returnValue(array(array('Name' => 'RegValue'))));
        $this->_customFields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $this->_customFields->expects($this->once())
                            ->method('getPropertyTypes')
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
    protected function _getForm()
    {
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => new \Zend\Mvc\I18n\Translator(new \Zend\Mvc\I18n\DummyTranslator),
                'registryValue' => $this->_registryValue,
                'customFields' => $this->_customFields,
            )
        );
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $filter = $this->_form->get('filter');
        $this->assertInstanceOf('Zend\Form\Element\Select', $filter);
        $filters = $filter->getValueOptions();
        $this->assertContains('Software: Name', $filters); // Hardcoded
        $this->assertContains('Registry: RegValue', $filters); // dynamically added
        $this->assertContains('User defined: Category', $filters); // dynamically added; renamed
        $this->assertContains('User defined: Integer', $filters); // dynamically added

        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('search'));
        $this->assertInstanceOf('Zend\Form\Element\Select', $this->_form->get('operator'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $this->_form->get('invert'));
    }

    public function testInitInvalidDatatype()
    {
        $registryValue = $this->getMock('Model_RegistryValue');
        $registryValue->expects($this->any())
                      ->method('fetchAll')
                      ->will($this->returnValue(array()));
        $customFields = $this->getMockBuilder('Model_UserDefinedInfo')->disableOriginalConstructor()->getMock();
        $customFields->expects($this->once())
                     ->method('getPropertyTypes')
                     ->will($this->returnValue(array('test' => 'invalid')));
        $form = new \Console\Form\Search(
            null,
            array(
                'translator' => new \Zend\Mvc\I18n\Translator(new \Zend\Mvc\I18n\DummyTranslator),
                'registryValue' => $registryValue,
                'customFields' => $customFields,
            )
        );
        $this->setExpectedException('UnexpectedValueException');
        $form->init();
    }

    public function testSetDataText()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.TAG',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
    }

    public function testSetDataClob()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Clob',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1000000', $this->_form->get('search')->getValue());
    }

    public function testSetDataInteger()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Integer',
            'search' => '1000000',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.000.000', $this->_form->get('search')->getValue());
    }

    public function testSetDataFloat()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Float',
            'search' => '1000000.1234',
        );
        $this->_form->setData($data);
        $this->assertEquals('1.000.000,1234', $this->_form->get('search')->getValue());
    }

    public function testSetDataDate()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Date',
            'search' => new \Zend_Date('2014-05-01'),
        );
        $this->_form->setData($data);
        $this->assertEquals('01.05.2014', $this->_form->get('search')->getValue());
    }

    public function testSetDataNoSearch()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.TAG',
        );
        $this->_form->setData($data); // Must not generate error
    }

    public function testInputFilterText()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Clob',
            'search' => ' test ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(' test ', $this->_form->getData()['search']);

        $data['search'] = '';
        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterInteger()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Integer',
            'search' => ' 1.234 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234, $this->_form->getData()['search']);

        $data['search'] = '';
        $this->_form->setRawData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterFloat()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Float',
            'search' => ' 1.234,5678 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(1234.5678, $this->_form->getData()['search']);

        $data['search'] = '';
        $this->_form->setRawData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterDate()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Date',
            'search' => ' 2.5.2014 ',
            'operator' => 'eq',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );

        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('02.05.2014', $this->_form->getData()['search']->get(\Zend_Date::DATE_MEDIUM));

        $data['search'] = '';
        $this->_form->setRawData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnTextOperator()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Clob',
            'search' => '1',
            'operator' => 'like',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'ne';
        $this->_form->setRawData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterOnOrdinalOperator()
    {
        $data = array(
            'filter' => 'UserDefinedInfo.Integer',
            'search' => '1',
            'operator' => 'ne',
            'invert' => '0',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setRawData($data);
        $this->assertTrue($this->_form->isValid());
        $data['operator'] = 'like';
        $this->_form->setRawData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testInvalidFilter()
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid filter: invalidFilter');
        $this->_form->setData(array('filter' => 'invalidFilter'));
    }

    public function testMissingFilterOnSearchValidation()
    {
        $this->setExpectedException('LogicException', 'No filter submitted');
        $this->_form->validateSearch('value', array('search' => 'value'));
    }

    public function testMissingFilterOnOperatorValidation()
    {
        $this->setExpectedException('LogicException', 'No filter submitted');
        $this->_form->validateOperator('value', array('search' => 'value'));
    }

    public function testRender()
    {
        $view = $this->_createView();
        $document = new \Zend\Dom\Document(
            $this->_form->render($view)
        );

        $result = Query::execute(
            '//select[@name="filter"][@onchange="filterChanged();"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="search"][@type="text"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//select[@name="operator"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="invert"][@type="checkbox"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute(
            '//input[@name="customSearch"][@type="submit"]',
            $document
        );
        $this->assertCount(1, $result);

        $headScript = $view->headScript()->toString();
        $this->assertContains('function filterChanged(', $headScript);
    }

    public function testRenderNoPreset()
    {
        $view = $this->_createView();
        $this->_form->render($view);
        $headScript = $view->headScript();
        $this->assertNotContains(
            'document.getElementById("form_search").elements["operator"].value = "eq";',
            $headScript
        );
    }

    public function testRenderWithPreset()
    {
        $this->_form->get('operator')->setValue('eq');
        $view = $this->_createView();
        $this->_form->render($view);
        $headScript = $view->headScript()->toString();
        $this->assertContains(
            'document.getElementById("form_search").elements["operator"].value = "eq";',
            $headScript
        );
    }
}
