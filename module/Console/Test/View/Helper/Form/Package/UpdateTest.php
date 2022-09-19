<?php

/**
 * Tests for the Update Helper
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

namespace Console\Test\View\Helper\Form\Package;

use ArrayIterator;
use Console\Form\Package\Build as BuildForm;
use Console\View\Helper\ConsoleScript;
use Console\View\Helper\Form\Package\Build as BuildHelper;
use Console\View\Helper\Form\Package\Update;
use Laminas\Form\ElementInterface;
use Laminas\Form\FieldsetInterface;
use Laminas\Form\FormInterface;
use Laminas\Form\View\Helper\FormElementErrors;
use Laminas\Form\View\Helper\FormRow;
use Laminas\View\Renderer\PhpRenderer;

class UpdateTest extends \Library\Test\View\Helper\AbstractTest
{
    protected function getHelperName()
    {
        return 'consoleFormPackageUpdate';
    }

    public function testRender()
    {
        $form = $this->createStub(BuildForm::class);

        $consoleScript = $this->createMock(ConsoleScript::class);
        $consoleScript->expects($this->once())->method('__invoke')->with('form_package.js');

        $build = $this->createMock(BuildHelper::class);
        $build->expects($this->once())->method('initLabels')->with($form);

        $view = $this->createStub(PhpRenderer::class);
        $view->method('plugin')->willReturnMap([
            [ConsoleScript::class, null, $consoleScript],
            [BuildHelper::class, null, $build],
        ]);

        $helper = $this->createPartialMock(Update::class, ['getView', 'renderForm']);
        $helper->method('getView')->willReturn($view);
        $helper->method('renderForm')->with($form)->willReturn('rendered form');

        $this->assertEquals('rendered form', $helper->render($form));
    }

    public function testRenderContent()
    {
        $formElementErrors = $this->createMock(FormElementErrors::class);
        $formElementErrors->expects($this->once())->method('setAttributes')->with(['class' => 'errors']);

        $element = $this->createStub(ElementInterface::class);

        $formRow = $this->createMock(FormRow::class);
        $formRow->method('__invoke')->with($element)->willReturn('<element>');

        $view = $this->createStub(PhpRenderer::class);
        $view->method('plugin')->willReturnMap([
            ['formElementErrors', null, $formElementErrors],
            ['formRow', null, $formRow],
        ]);

        $deploy = $this->createStub(FieldsetInterface::class);
        $deploy->method('getName')->willReturn('Deploy');

        $iterator = new ArrayIterator([$deploy, $element]);

        $form = $this->createStub(FormInterface::class);
        $form->method('getIterator')->willReturn($iterator);

        $helper = $this->createPartialMock(Update::class, ['getView', 'renderDeployFieldset']);
        $helper->method('getView')->willReturn($view);
        $helper->method('renderDeployFieldset')->with($deploy)->willReturn('<deploy_fieldset>');

        $this->assertEquals('<deploy_fieldset><element>', $helper->renderContent($form));
    }

    public function testRenderDeployFieldset()
    {
        $fieldset = $this->createStub(FieldsetInterface::class);
        $fieldset->method('getLabel')->willReturn('untranslated_label');

        $view = $this->createStub(PhpRenderer::class);
        $view->method('__call')->willReturnMap([
            ['escapeHtml', ['translated_label'], 'escaped_label'],
            ['translate', ['untranslated_label'], 'translated_label'],
            ['consoleFormFieldset', [$fieldset, FormRow::LABEL_APPEND], '<fieldset>'],
        ]);

        $helper = $this->createPartialMock(Update::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            '<div class="label">escaped_label</div><fieldset>',
            $helper->renderDeployFieldset($fieldset)
        );
    }
}
