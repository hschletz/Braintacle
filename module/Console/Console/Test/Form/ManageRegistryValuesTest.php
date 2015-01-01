<?php
/**
 * Tests for ManageRegistryValues
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

use Zend\Dom\Document\Query as Query;

/**
 * Tests for ManageRegistryValues
 */
class ManageRegistryValuesTest extends \Console\Test\AbstractFormTest
{
    /**
     * Config mock object
     * @var \Model\Config
     */
    protected $_config;

    /**
     * RegistryValue mock object
     * @var \Model_RegistryValue
     */
    protected $_registryValue;

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
     * Set up Config mock object
     */
    public function setUp()
    {
        $this->_config = $this->getMockBuilder('Model\Config')
                              ->disableOriginalconstructor()
                              ->getMock();
        $this->_config->expects($this->any())
                      ->method('__get')
                      ->with('inspectRegistry')
                      ->will($this->returnValue('1'));
        $this->_registryValue = $this->getMock('Model_RegistryValue');
        $this->_registryValue->expects($this->once())
                             ->method('fetchAll')
                             ->will($this->returnValue($this->_values));
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\ManageRegistryValues(
            null,
            array(
                'config' => $this->_config,
                'registryValue' => $this->_registryValue,
            )
        );
        $form->init();
        return $form;
    }

    /**
     * Tests for init()
     */
    public function testInit()
    {
        $fieldset = $this->_form->get('inspect');
        $this->assertInstanceOf('Zend\Form\Fieldset', $fieldset);
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $fieldset->get('inspect'));
        $this->assertTrue($fieldset->get('inspect')->isChecked());

        $fieldset = $this->_form->get('existing');
        $this->assertInstanceOf('Zend\Form\Fieldset', $fieldset);
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldset->get('value_1_name'));
        $this->assertEquals('Test1', $fieldset->get('value_1_name')->getValue());
        $this->assertEquals('a\b\c', $fieldset->get('value_1_name')->getLabel());
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldset->get('value_2_name'));
        $this->assertEquals('Test2', $fieldset->get('value_2_name')->getValue());
        $this->assertEquals('d\e\f', $fieldset->get('value_2_name')->getLabel());

        $fieldset = $this->_form->get('new_value');
        $this->assertInstanceOf('Zend\Form\Fieldset', $fieldset);
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldset->get('name'));
        $this->assertInstanceOf('Zend\Form\Element\Select', $fieldset->get('root_key'));
        $this->assertEquals(\Model_RegistryValue::rootKeys(), $fieldset->get('root_key')->getValueOptions());
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldset->get('subkeys'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldset->get('value'));
        $this->assertInstanceOf('\Library\Form\Element\Submit', $fieldset->get('submit'));
    }

    /**
     * Tests for input filter provided by init()
     */
    public function testInputFilter()
    {
        // Unchanged values (valid)
        $data = array(
            'inspect' => array(
                'inspect' => '1',
            ),
            'existing' => array(
                'value_1_name' => 'Test1',
                'value_2_name' => 'Test2',
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
        $data['existing']['value_1_name'] = '';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('isEmpty', $this->_form->getMessages()['existing']['value_1_name']);

        // Fieldset "existing": too long value should fail
        $data['existing']['value_1_name'] = $string256;
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('stringLengthTooLong', $this->_form->getMessages()['existing']['value_1_name']);

        // Fieldset "existing": existing value should fail (test StringTrim too)
        $data['existing']['value_1_name'] = ' test2';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertArrayHasKey('inArray', $this->_form->getMessages()['existing']['value_1_name']);

        // Fieldset "existing": Renaming value, including mere case change, should pass (test StringTrim too)
        $data['existing']['value_1_name'] = ' test1';
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals('test1', $this->_form->getData()['existing']['value_1_name']);

        // Fieldset "new_value": If 'name' is nonempty, 'subkeys' must also be nonempty (test StringTrim too)
        $data['new_value']['name'] = ' test';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $this->assertEquals(
            array('callbackValue' => "Eingabe erforderlich"),
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

    /**
     * Tests for render()
     */
    public function testRender()
    {
        $this->_form->get('existing')->get('value_1_name')->setMessages(array('test' => 'Message1'));
        $this->_form->get('new_value')->get('subkeys')->setMessages(array('test' => 'Message2'));
        $document = new \Zend\Dom\Document(
            $this->_form->render($this->_createView())
        );

        // Test state of inspect checkbox
        $result = Query::execute(
            '//input[@name="inspect[inspect]"][@checked="checked"]',
            $document
        );
        $this->assertCount(1, $result);

        // Test table with existing values
        $result = Query::execute('//tr', $document);
        $this->assertCount(2, $result);

        $result = Query::execute(
            '//td//input[@name="existing[value_1_name]"][@value="Test1"]',
            $document
        );
        $this->assertCount(1, $result);

        $result = Query::execute('//td', $document);
        $this->assertEquals("\na\b\c\n", $result[1]->textContent);

        $result = Query::execute(
            '//td//a[@href="/console/preferences/deleteregistryvalue/?id=1"]',
            $document
        );
        $this->assertCount(1, $result);

        // Test elements for new value
        $result = Query::execute('//input[@name="new_value[name]"]', $document);
        $this->assertCount(1, $result);

        $result = Query::execute('//select[@name="new_value[root_key]"]', $document);
        $this->assertCount(1, $result);

        $result = Query::execute('//input[@name="new_value[subkeys]"]', $document);
        $this->assertCount(1, $result);

        $result = Query::execute('//input[@name="new_value[value]"]', $document);
        $this->assertCount(1, $result);

        // Test submit button
        $result = Query::execute('//input[@type="submit"]', $document);
        $this->assertCount(1, $result);

        // Test message rendering
        $result = Query::execute('//ul[@class="errors"]/li', $document);
        $this->assertCount(2, $result);
        $this->assertEquals("Message1", $result[0]->textContent);
        $this->assertEquals("Message2", $result[1]->textContent);
    }

    /**
     * Tests for process()
     */
    public function testProcess()
    {
        // Test data for first run: no value to add
        $data1 = array(
            'inspect' => array(
                'inspect' => '1',
            ),
            'existing' => array(
                'value_1_name' => 'name1',
                'value_2_name' => 'name2',
            ),
            'new_value' => array(
                'name' => '',
                'root_key' => 'root_key',
                'subkeys' => 'subkeys',
                'value' => 'value'
            ),
        );

        // Test data for second run: add value
        $data2 = $data1;
        $data2['new_value']['name'] = 'name';

        // 2 Mock values: Id 1/2, renamed to name1/name2
        $value1 = $this->getMock('Model_RegistryValue');
        $value1->expects($this->exactly(2))
               ->method('rename')
               ->with('name1');
        $value1->expects($this->any())
               ->method('offsetGet')
               ->will(
                   $this->returnValueMap(
                       array(
                           array('Id', 1),
                       )
                   )
               );
        $value2 = $this->getMock('Model_RegistryValue');
        $value2->expects($this->exactly(2))
               ->method('rename')
               ->with('name2');
        $value2->expects($this->any())
               ->method('offsetGet')
               ->will(
                   $this->returnValueMap(
                       array(
                           array('Id', 2),
                       )
                   )
               );

        // Mock registryValue prototype: delivers the 2 values above
        $registryValue = $this->getMock('Model_RegistryValue');
        $registryValue->expects($this->once())
                      ->method('fetchAll')
                      ->will($this->returnValue(array($value1, $value2)));
        // Value will be added only once (in the second run)
        $registryValue->expects($this->once())
                      ->method('add')
                      ->with('name', 'root_key', 'subkeys', 'value');

        // Test set config value
        $this->_config->expects($this->exactly(2))
                      ->method('__set')
                      ->with('inspectRegistry', '1');

        // Set up form mock
        $form = $this->getMockBuilder('Console\Form\ManageRegistryValues')
                     ->setMethods(array('getData'))
                     ->setConstructorArgs(
                         array(
                            null,
                            array(
                                'config' => $this->_config,
                                'registryValue' => $registryValue,
                            ),
                         )
                     )->getMock();
        // Deliver different data in each run
        $form->expects($this->exactly(2))
             ->method('getData')
             ->will($this->onConsecutiveCalls($data1, $data2));
        $form->init();

        $form->process(); // No value to add
        $form->process(); // Add value
    }
}
