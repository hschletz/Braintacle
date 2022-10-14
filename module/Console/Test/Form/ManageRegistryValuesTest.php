<?php

/**
 * Tests for ManageRegistryValues
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

use Console\Form\ManageRegistryValues;
use Model\Registry\RegistryManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for ManageRegistryValues
 */
class ManageRegistryValuesTest extends \Console\Test\AbstractFormTest
{
    /**
     * RegistryManager mock object
     * @var MockObject|RegistryManager
     */
    protected $_registryManager;

    /**
     * Mock registry values
     * @var array
     */
    protected $_values = array(
        array(
            'Id' => 1,
            'Name' => 'Test1',
            'FullPath' => 'a\b\c',
        ),
        array(
            'Id' => 2,
            'Name' => 'Test2',
            'FullPath' => 'd\e\f',
        ),
    );

    /**
     * "Test1" and "Test2"
     */
    protected $_name1 = 'VGVzdDE=';
    protected $_name2 = 'VGVzdDI=';

    public function setUp(): void
    {
        $resultSet = new \Laminas\Db\ResultSet\ResultSet();
        $resultSet->initialize($this->_values);
        $this->_registryManager = $this->createMock('Model\Registry\RegistryManager');
        $this->_registryManager->expects($this->once())
                               ->method('getValueDefinitions')
                               ->willReturn($resultSet);
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        $form = new \Console\Form\ManageRegistryValues(
            null,
            array('registryManager' => $this->_registryManager)
        );
        $form->init();
        return $form;
    }

    /**
     * Tests for init()
     */
    public function testInit()
    {
        $fieldset = $this->_form->get('existing');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldset);
        $this->assertEquals('Values', $fieldset->getLabel());
        $value1 = $fieldset->get($this->_name1);
        $this->assertInstanceOf('Laminas\Form\Element\Text', $value1);
        $this->assertEquals('Test1', $value1->getValue());
        $this->assertEquals('a\b\c', $value1->getLabel());
        $value2 = $fieldset->get($this->_name2);
        $this->assertInstanceOf('Laminas\Form\Element\Text', $value2);
        $this->assertEquals('Test2', $value2->getValue());
        $this->assertEquals('d\e\f', $value2->getLabel());

        $fieldset = $this->_form->get('new_value');
        $this->assertEquals('Add', $fieldset->getLabel());
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldset);
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldset->get('name'));
        $this->assertInstanceOf('Laminas\Form\Element\Select', $fieldset->get('root_key'));
        $this->assertEquals(\Model\Registry\Value::rootKeys(), $fieldset->get('root_key')->getValueOptions());
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldset->get('subkeys'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldset->get('value'));

        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('submit'));
    }

    /**
     * Tests for input filter provided by init()
     */
    public function testInputFilter()
    {
        // Unchanged values (valid)
        $data = array(
            'existing' => array(
                $this->_name1 => 'Test1',
                $this->_name2 => 'Test2',
            ),
            'new_value' => array(
                'name' => ' ', // Trimmed to empty string
                'root_key' => '2',
                'subkeys' => ' ', // Trimmed to empty string
                'value' => ''
            ),
            'submit' => 'Change',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $string256 = str_repeat('x', 256);

        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        // Fieldset "existing": empty value should fail
        $data['existing'][$this->_name1] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('isEmpty', $this->_form->getMessages()['existing'][$this->_name1]);

        // Fieldset "existing": too long value should fail
        $data['existing'][$this->_name1] = $string256;
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('stringLengthTooLong', $this->_form->getMessages()['existing'][$this->_name1]);

        // Fieldset "existing": existing value should fail (test StringTrim too)
        $data['existing'][$this->_name1] = ' test2';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('inArray', $this->_form->getMessages()['existing'][$this->_name1]);

        // Fieldset "existing": Renaming value, including mere case change, should pass (test StringTrim too)
        $data['existing'][$this->_name1] = ' test1';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('test1', $this->_form->getData()['existing'][$this->_name1]);

        // Fieldset "new_value": If 'name' is nonempty, 'subkeys' must also be nonempty (test StringTrim too)
        $data['new_value']['name'] = ' test';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertEquals(
            array('callbackValue' => "TRANSLATE(Value is required and can't be empty)"),
            $this->_form->getMessages()['new_value']['subkeys']
        );
        $this->assertEquals('test', $this->_form->getData()['new_value']['name']);

        $data['new_value']['subkeys'] = ' a\b\c';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('a\b\c', $this->_form->getData()['new_value']['subkeys']);

        // Fieldset "new_value": StringTrim filter on 'value'
        $data['new_value']['value'] = ' test';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('test', $this->_form->getData()['new_value']['value']);

        // Fieldset "new_value": test too long values
        $data['new_value']['name'] = $string256;
        $data['new_value']['subkeys'] = $string256;
        $data['new_value']['value'] = $string256;
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertCount(3, $this->_form->getMessages()['new_value']);
    }

    public function testProcessAdd()
    {
        $data = array(
            'inspect' => array(
                'inspect' => '1',
            ),
            'existing' => array(),
            'new_value' => array(
                'name' => 'name',
                'root_key' => 'root_key',
                'subkeys' => 'subkeys',
                'value' => 'value'
            ),
        );

        $registryManager = $this->createMock('Model\Registry\RegistryManager');
        $registryManager->expects($this->once())
                        ->method('addValueDefinition')
                        ->with('name', 'root_key', 'subkeys', 'value');
        $registryManager->expects($this->never())->method('renameValueDefinition');

        $form = $this->createPartialMock(ManageRegistryValues::class, ['getData', 'getOption']);
        $form->expects($this->once())->method('getData')->willReturn($data);
        $form->method('getOption')->with('registryManager')->willReturn($registryManager);

        $form->process();
    }

    public function testProcessRename()
    {
        $data = array(
            'inspect' => array(
                'inspect' => '1',
            ),
            'existing' => array(
                $this->_name1 => 'Test1_new',
                $this->_name2 => 'Test2',
            ),
            'new_value' => array(
                'name' => '',
                'root_key' => 'root_key',
                'subkeys' => 'subkeys',
                'value' => 'value'
            ),
        );

        $value1 = ['Name' => 'Test1'];
        $value2 = ['Name' => 'Test2'];

        $registryManager = $this->createMock('Model\Registry\RegistryManager');
        $registryManager->expects($this->never())->method('addValueDefinition');
        $registryManager->expects($this->exactly(2))
                        ->method('renameValueDefinition')
                        ->withConsecutive(
                            array('Test1', 'Test1_new'),
                            array('Test2', 'Test2')
                        );

        $form = $this->createPartialMock(ManageRegistryValues::class, ['getData', 'getOption', 'getDefinedValues']);
        $form->expects($this->once())->method('getData')->willReturn($data);
        $form->method('getOption')->with('registryManager')->willReturn($registryManager);
        $form->method('getDefinedValues')->willReturn([$value1, $value2]);

        $form->process();
    }
}
