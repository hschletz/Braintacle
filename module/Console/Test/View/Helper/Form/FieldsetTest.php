<?php
/**
 * Tests for the Fieldset Helper
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

use ArrayIterator;
use Console\View\Helper\Form\Fieldset;
use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\View\Helper\FormElementErrors;
use Laminas\View\Renderer\PhpRenderer;

class FieldsetTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function _getHelperName()
    {
        return 'consoleFormFieldset';
    }

    public function testInvoke()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('render'))
                       ->getMock();
        $helper->method('render')->with($fieldset)->willReturn('FIELDSET');

        $this->assertEquals('FIELDSET', $helper($fieldset));
    }

    public function testRenderWithForm()
    {
        $fieldset = $this->createMock('Laminas\Form\FormInterface');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('renderElements', 'renderFieldsetElement'))
                       ->getMock();
        $helper->method('renderElements')->with($fieldset)->willReturn('ELEMENTS');
        $helper->expects($this->never())->method('renderFieldsetElement');

        $this->assertEquals('ELEMENTS', $helper->render($fieldset));
    }

    public function testRenderWithFieldset()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('renderElements', 'renderFieldsetElement'))
                       ->getMock();
        $helper->method('renderElements')->with($fieldset)->willReturn('ELEMENTS');
        $helper->method('renderFieldsetElement')->with($fieldset, 'ELEMENTS')->willReturn('FIELDSET');

        $this->assertEquals('FIELDSET', $helper->render($fieldset));
    }

    public function testRenderFieldsetElementWithContent()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');
        $fieldset->method('getAttributes')->willReturn('ATTRIBUTES');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->with('htmlElement', array('fieldset', 'LABEL<div>CONTENT</div>', 'ATTRIBUTES'))
             ->willReturn('FIELDSET');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('renderElements', 'renderLabel', 'getView'))
                       ->getMock();
        $helper->expects($this->never())->method('renderElements');
        $helper->method('renderLabel')->with($fieldset)->willReturn('LABEL');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('FIELDSET', $helper->renderFieldsetElement($fieldset, 'CONTENT'));
    }

    public function testRenderFieldsetElementWithoutContent()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');
        $fieldset->method('getAttributes')->willReturn('ATTRIBUTES');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->with('htmlElement', array('fieldset', 'LABEL<div>CONTENT</div>', 'ATTRIBUTES'))
             ->willReturn('FIELDSET');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('renderElements', 'renderLabel', 'getView'))
                       ->getMock();
        $helper->method('renderElements')->with($fieldset)->willReturn('CONTENT');
        $helper->method('renderLabel')->with($fieldset)->willReturn('LABEL');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('FIELDSET', $helper->renderFieldsetElement($fieldset));
    }

    public function testRenderLabelWithoutLabel()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');
        $fieldset->method('getLabel')->willReturn('');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('getView'))
                       ->getMock();
        $helper->expects($this->never())->method('getView');

        $this->assertEquals('', $helper->renderLabel($fieldset));
    }

    public function testRenderLabelWithLabel()
    {
        $fieldset = $this->createMock('Laminas\Form\FieldsetInterface');
        $fieldset->method('getLabel')->willReturn('LABEL');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->withConsecutive(
                 array('translate', array('LABEL')),
                 array('escapeHtml', array('TRANSLATED'))
             )
             ->willReturnOnConsecutiveCalls('TRANSLATED', 'ESCAPED');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('getView'))
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<legend>ESCAPED</legend>', $helper->renderLabel($fieldset));
    }

    public function testRenderElements()
    {
        $subFieldset = $this->createStub(FieldsetInterface::class);
        $subElement = $this->createStub(ElementInterface::class);

        $iterator = new ArrayIterator([$subFieldset, $subElement]);

        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getIterator')->willReturn($iterator);

        $formElementErrors = $this->createMock(FormElementErrors::class);
        $formElementErrors->expects($this->once())->method('setAttributes')->with(['class' => 'errors']);

        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->withConsecutive(
            ['formElementErrors', []],
            ['formRow', [$subElement]],
        )->willReturnOnConsecutiveCalls($formElementErrors, '<FORMROW>');

        $helper = $this->createPartialMock(Fieldset::class, ['render', 'getView']);
        $helper->method('render')->willReturn('<FIELDSET>');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<FIELDSET><FORMROW>', $helper->renderElements($fieldset));
    }
}
