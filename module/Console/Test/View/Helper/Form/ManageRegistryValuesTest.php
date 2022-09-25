<?php

/**
 * Tests for the ManageRegistryValues Helper
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
use Console\View\Helper\Form\Fieldset as FieldsetHelper;
use Console\View\Helper\Form\ManageRegistryValues;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Form\FormInterface;
use Laminas\Stdlib\PriorityList;
use Laminas\View\Renderer\PhpRenderer;

class ManageRegistryValuesTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function getHelperName()
    {
        return 'consoleFormManageRegistryValues';
    }

    public function testRenderContentWithoutExisting()
    {
        $fieldsetExisting = $this->createMock(Fieldset::class);
        $fieldsetExisting->method('getName')->willReturn('existing');
        $fieldsetExisting->expects($this->once())->method('count')->willReturn(0);

        $fieldsetNewValue = $this->createStub(Fieldset::class);
        $fieldsetNewValue->method('getName')->willReturn('new_value');

        $submit = $this->createStub(Submit::class);

        $form = $this->createStub(FormInterface::class);
        $form->method('getIterator')->willReturn(
            new ArrayIterator(array($fieldsetExisting, $fieldsetNewValue, $submit))
        );

        $fieldsetHelper = $this->createMock(FieldsetHelper::class);
        $fieldsetHelper->expects($this->never())->method('renderFieldsetElement');
        $fieldsetHelper->method('render')->with($fieldsetNewValue)->willReturn('<fieldset_new>');

        $view = $this->createMock(PhpRenderer::class);
        $view->method('plugin')->with('consoleFormFieldset')->willReturn($fieldsetHelper);
        $view->method('__call')->with('formRow', [$submit])->willReturn('<submit>');

        $helper = $this->createPartialMock(ManageRegistryValues::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<fieldset_new><submit>', $helper->renderContent($form));
    }

    public function testRenderContentWithExisting()
    {
        $name1 = sprintf('existing[%s]', base64_encode('name1'));
        $name2 = sprintf('existing[%s]', base64_encode('name2'));

        $input1 = $this->createStub(Text::class);
        $input1->method('getName')->willReturn($name1);
        $input1->method('getLabel')->willReturn('label1');

        $input2 = $this->createStub(Text::class);
        $input2->method('getName')->willReturn($name2);
        $input2->method('getLabel')->willReturn('label2');

        $iterator = new PriorityList();
        $iterator->insert('1', $input1, 1);
        $iterator->insert('2', $input2, 0);

        $fieldsetExisting = $this->createStub(Fieldset::class);
        $fieldsetExisting->method('getName')->willReturn('existing');
        $fieldsetExisting->method('count')->willReturn(2);
        $fieldsetExisting->method('getIterator')->willReturn($iterator);

        $fieldsetNewValue = $this->createStub(Fieldset::class);
        $fieldsetNewValue->method('getName')->willReturn('new_value');

        $submit = $this->createStub(Submit::class);

        $form = $this->createStub(FormInterface::class);
        $form->method('getIterator')->willReturn(
            new ArrayIterator(array($fieldsetExisting, $fieldsetNewValue, $submit))
        );

        $fieldsetHelper = $this->createMock(FieldsetHelper::class);
        $fieldsetHelper->method('renderFieldsetElement')
                       ->with($fieldsetExisting, '<ROW1><ERRORS1><ROW2><ERRORS2>')
                       ->willReturn('<fieldset_existing>');
        $fieldsetHelper->method('render')->with($fieldsetNewValue)->willReturn('<fieldset_new>');

        $view = $this->createMock(PhpRenderer::class);
        $view->method('plugin')->with('consoleFormFieldset')->willReturn($fieldsetHelper);
        $view->method('__call')->willReturnMap([
            ['consoleUrl', ['preferences', 'deleteregistryvalue', ['name' => 'name1']], 'URL1'],
            ['consoleUrl', ['preferences', 'deleteregistryvalue', ['name' => 'name2']], 'URL2'],
            ['escapeHtml', ['label1'], 'LABEL1'],
            ['escapeHtml', ['label2'], 'LABEL2'],
            ['formElement', [$input1], '<input1>'],
            ['formElement', [$input2], '<input2>'],
            ['formElementErrors', [$input1], '<ERRORS1>'],
            ['formElementErrors', [$input2], '<ERRORS2>'],
            ['formRow', [$submit], '<submit>'],
            ['htmlElement', ['a', 'DELETE', ['href' => 'URL1']], '<a>DELETE1</a>'],
            ['htmlElement', ['a', 'DELETE', ['href' => 'URL2']], '<a>DELETE2</a>'],
            ['htmlElement', ['label', '<input1><span>LABEL1</span><a>DELETE1</a>'], '<ROW1>'],
            ['htmlElement', ['label', '<input2><span>LABEL2</span><a>DELETE2</a>'], '<ROW2>'],
            ['htmlElement', ['span', 'LABEL1'], '<span>LABEL1</span>'],
            ['htmlElement', ['span', 'LABEL2'], '<span>LABEL2</span>'],
            ['translate', ['Delete'], 'DELETE'],
        ]);

        $helper = $this->createPartialMock(ManageRegistryValues::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('<fieldset_existing><fieldset_new><submit>', $helper->renderContent($form));
    }
}
