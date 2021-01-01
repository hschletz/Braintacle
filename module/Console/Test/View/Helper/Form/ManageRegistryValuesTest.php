<?php
/**
 * Tests for the ManageRegistryValues Helper
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\View\Helper\Form;

class ManageRegistryValuesTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function _getHelperName()
    {
        return 'consoleFormManageRegistryValues';
    }

    public function testRenderElementsWithoutExisting()
    {
        $fieldsetExisting = $this->createMock('Laminas\Form\Fieldset');
        $fieldsetExisting->method('getName')->willReturn('existing');
        $fieldsetExisting->expects($this->once())->method('count')->willReturn(0);

        $fieldsetNewValue = $this->createMock('Laminas\Form\Fieldset');
        $fieldsetNewValue->method('getName')->willReturn('new_value');

        $submit = $this->createMock('Laminas\Form\Element\Submit');

        $form = $this->createMock('Laminas\Form\FormInterface');
        $form->method('getIterator')->willReturn(
            new \ArrayIterator(array($fieldsetExisting, $fieldsetNewValue, $submit))
        );

        $fieldsetHelper = $this->createMock('Console\View\Helper\Form\Fieldset');
        $fieldsetHelper->expects($this->never())->method('renderFieldsetElement');
        $fieldsetHelper->method('render')->with($fieldsetNewValue)->willReturn('<fieldset_new>');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('plugin')->with('consoleFormFieldset')->willReturn($fieldsetHelper);
        $view->method('__call')->with('formRow', array($submit))->willReturn('<submit>');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(array('renderElements'))
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<fieldset_new><submit>', $helper->renderElements($form));
    }

    public function testRenderElementsWithExisting()
    {
        $name1 = sprintf('existing[%s]', base64_encode('name1'));
        $name2 = sprintf('existing[%s]', base64_encode('name2'));

        $input1 = $this->createMock('Laminas\Form\Element\Text');
        $input1->method('getName')->willReturn($name1);
        $input1->method('getLabel')->willReturn('label1');

        $input2 = $this->createMock('Laminas\Form\Element\Text');
        $input2->method('getName')->willReturn($name2);
        $input2->method('getLabel')->willReturn('label2');

        $fieldsetExisting = $this->createMock('Laminas\Form\Fieldset');
        $fieldsetExisting->method('getName')->willReturn('existing');
        $fieldsetExisting->method('count')->willReturn(2);
        $fieldsetExisting->method('getIterator')->willReturn(new \ArrayIterator(array($input1, $input2)));

        $fieldsetNewValue = $this->createMock('Laminas\Form\Fieldset');
        $fieldsetNewValue->method('getName')->willReturn('new_value');

        $submit = $this->createMock('Laminas\Form\Element\Submit');

        $form = $this->createMock('Laminas\Form\FormInterface');
        $form->method('getIterator')->willReturn(
            new \ArrayIterator(array($fieldsetExisting, $fieldsetNewValue, $submit))
        );

        $fieldsetHelper = $this->createMock('Console\View\Helper\Form\Fieldset');
        $fieldsetHelper->method('renderFieldsetElement')
                       ->with($fieldsetExisting, '<ROW1><ERRORS1><ROW2><ERRORS2>')
                       ->willReturn('<fieldset_existing>');
        $fieldsetHelper->method('render')->with($fieldsetNewValue)->willReturn('<fieldset_new>');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('plugin')->with('consoleFormFieldset')->willReturn($fieldsetHelper);
        $view->method('__call')->willReturnMap(
            array(
                array('consoleUrl', array('preferences', 'deleteregistryvalue', array('name' => 'name1')), 'URL1'),
                array('consoleUrl', array('preferences', 'deleteregistryvalue', array('name' => 'name2')), 'URL2'),
                array('escapeHtml', array('label1'), 'LABEL1'),
                array('escapeHtml', array('label2'), 'LABEL2'),
                array('formElement', array($input1), '<input1>'),
                array('formElement', array($input2), '<input2>'),
                array('formElementErrors', array($input1), '<ERRORS1>'),
                array('formElementErrors', array($input2), '<ERRORS2>'),
                array('formRow', array($submit), '<submit>'),
                array('htmlElement', array('a', 'DELETE', array('href' => 'URL1')), '<a>DELETE1</a>'),
                array('htmlElement', array('a', 'DELETE', array('href' => 'URL2')), '<a>DELETE2</a>'),
                array('htmlElement', array('label', '<input1><span>LABEL1</span><a>DELETE1</a>'), '<ROW1>'),
                array('htmlElement', array('label', '<input2><span>LABEL2</span><a>DELETE2</a>'), '<ROW2>'),
                array('htmlElement', array('span', 'LABEL1'), '<span>LABEL1</span>'),
                array('htmlElement', array('span', 'LABEL2'), '<span>LABEL2</span>'),
                array('translate', array('Delete'), 'DELETE'),
            )
        );

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(array('renderElements'))
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<fieldset_existing><fieldset_new><submit>', $helper->renderElements($form));
    }
}
