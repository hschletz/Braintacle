<?php

/**
 * Tests for the Fieldset Helper
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

namespace Console\Test\View\Helper\Form;

use ArrayIterator;
use Console\View\Helper\Form\Fieldset;
use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormElementErrors;
use Laminas\Form\View\Helper\FormRow;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class FieldsetTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function getHelperName()
    {
        return 'consoleFormFieldset';
    }

    public function testInvokeWithDefaultLabelPosition()
    {
        $fieldset = $this->createMock(FieldsetInterface::class);

        /** @var MockObject|Fieldset|callable */
        $helper = $this->createPartialMock(Fieldset::class, ['render']);
        $helper->method('render')->with($fieldset, FormRow::LABEL_PREPEND)->willReturn('FIELDSET');

        $this->assertEquals('FIELDSET', $helper($fieldset));
    }

    public function testInvokeWithExplicitLabelPosition()
    {
        /** @var Stub|FieldsetInterface */
        $fieldset = $this->createStub(FieldsetInterface::class);

        /** @var MockObject|Fieldset|callable */
        $helper = $this->createPartialMock(Fieldset::class, ['render']);
        $helper->method('render')->with($fieldset, FormRow::LABEL_APPEND)->willReturn('FIELDSET');

        $this->assertEquals('FIELDSET', $helper($fieldset, FormRow::LABEL_APPEND));
    }

    public function testRenderWithForm()
    {
        $fieldset = $this->createMock(FormInterface::class);

        $helper = $this->createPartialMock(Fieldset::class, ['renderElements', 'renderFieldsetElement']);
        $helper->method('renderElements')->with($fieldset, FormRow::LABEL_PREPEND)->willReturn('ELEMENTS');
        $helper->expects($this->never())->method('renderFieldsetElement');

        $this->assertEquals('ELEMENTS', $helper->render($fieldset));
    }

    public function testRenderWithFieldset()
    {
        $fieldset = $this->createMock(FieldsetInterface::class);

        $helper = $this->createPartialMock(Fieldset::class, ['renderElements', 'renderFieldsetElement']);
        $helper->method('renderElements')->with($fieldset, FormRow::LABEL_PREPEND)->willReturn('ELEMENTS');
        $helper->method('renderFieldsetElement')->with($fieldset, 'ELEMENTS')->willReturn('FIELDSET');

        $this->assertEquals('FIELDSET', $helper->render($fieldset));
    }

    public function testRenderWithExplicitLabelPosition()
    {
        $fieldset = $this->createMock(FormInterface::class);

        $helper = $this->createPartialMock(Fieldset::class, ['renderElements', 'renderFieldsetElement']);
        $helper->expects($this->once())->method('renderElements')->with($fieldset, FormRow::LABEL_APPEND);

        $helper->render($fieldset, FormRow::LABEL_APPEND);
    }

    public function testRenderFieldsetElementWithContent()
    {
        /** @var Stub|FieldsetInterface */
        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getAttributes')->willReturn(['ATTRIBUTES']);

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->with('htmlElement', array('fieldset', 'LABEL<div>CONTENT</div>', ['ATTRIBUTES']))
             ->willReturn('FIELDSET');

        $helper = $this->createPartialMock(Fieldset::class, ['renderElements', 'renderLabel', 'getView']);
        $helper->expects($this->never())->method('renderElements');
        $helper->method('renderLabel')->with($fieldset)->willReturn('LABEL');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('FIELDSET', $helper->renderFieldsetElement($fieldset, 'CONTENT'));
    }

    public function testRenderFieldsetElementWithoutContent()
    {
        /** @var Stub|FieldsetInterface */
        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getAttributes')->willReturn(['ATTRIBUTES']);

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->with('htmlElement', array('fieldset', 'LABEL<div>CONTENT</div>', ['ATTRIBUTES']))
             ->willReturn('FIELDSET');

        $helper = $this->createPartialMock(Fieldset::class, ['renderElements', 'renderLabel', 'getView']);
        $helper->method('renderElements')->with($fieldset)->willReturn('CONTENT');
        $helper->method('renderLabel')->with($fieldset)->willReturn('LABEL');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('FIELDSET', $helper->renderFieldsetElement($fieldset));
    }

    public function testRenderLabelWithoutLabel()
    {
        /** @var Stub|FieldsetInterface */
        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getLabel')->willReturn('');

        $helper = $this->createPartialMock(Fieldset::class, ['getView']);
        $helper->expects($this->never())->method('getView');

        $this->assertEquals('', $helper->renderLabel($fieldset));
    }

    public function testRenderLabelWithLabel()
    {
        /** @var Stub|FieldsetInterface */
        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getLabel')->willReturn('LABEL');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->withConsecutive(
                 array('translate', array('LABEL')),
                 array('escapeHtml', array('TRANSLATED'))
             )
             ->willReturnOnConsecutiveCalls('TRANSLATED', 'ESCAPED');

        $helper = $this->createPartialMock(Fieldset::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<legend>ESCAPED</legend>', $helper->renderLabel($fieldset));
    }

    public function testRenderElementsWithDefaultLabelPosition()
    {
        $subFieldset = $this->createStub(FieldsetInterface::class);
        $subElement = $this->createStub(ElementInterface::class);

        $iterator = new ArrayIterator([$subFieldset, $subElement]);

        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getIterator')->willReturn($iterator);

        $formElementErrors = $this->createMock(FormElementErrors::class);
        $formElementErrors->expects($this->once())->method('setAttributes')->with(['class' => 'errors']);

        $formRow = $this->createMock(FormRow::class);
        $formRow->method('__invoke')->with($subElement, FormRow::LABEL_PREPEND)->willReturn('<FORMROW>');

        $view = $this->createStub(PhpRenderer::class);
        $view->method('plugin')->willReturnMap([
            ['formElementErrors', null, $formElementErrors],
            ['formRow', null, $formRow]
        ]);

        $helper = $this->createPartialMock(Fieldset::class, ['render', 'getView']);
        $helper->method('render')->willReturn('<FIELDSET>');
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<FIELDSET><FORMROW>', $helper->renderElements($fieldset));
    }

    public function testRenderElementsWithExplicitLabelPosition()
    {
        $subElement = $this->createStub(ElementInterface::class);

        $iterator = new ArrayIterator([$subElement]);

        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getIterator')->willReturn($iterator);

        $formElementErrors = $this->createStub(FormElementErrors::class);

        $formRow = $this->createMock(FormRow::class);
        $formRow->expects($this->once())->method('__invoke')->with($subElement, FormRow::LABEL_APPEND);

        $view = $this->createStub(PhpRenderer::class);
        $view->method('plugin')->willReturnMap([
            ['formElementErrors', null, $formElementErrors],
            ['formRow', null, $formRow]
        ]);

        $helper = $this->createPartialMock(Fieldset::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $helper->renderElements($fieldset, FormRow::LABEL_APPEND);
    }
}
