<?php

/**
 * Tests for the ClientConfig Helper
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\ClientConfig as ClientConfigForm;
use Console\View\Helper\ConsoleScript;
use Console\View\Helper\Form\ClientConfig as ClientConfigHelper;
use Console\View\Helper\Form\Fieldset as FormFieldset;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Stdlib\PriorityList;
use Laminas\View\Renderer\PhpRenderer;
use Library\Test\View\Helper\AbstractTestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\ClientOrGroup;
use Model\Group\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class ClientConfigTest extends AbstractTestCase
{
    use MockeryPHPUnitIntegration;

    public function testHelperService()
    {
        $this->assertInstanceOf(ClientConfigHelper::class, $this->getHelper(ClientConfigHelper::class));
    }

    public function testRender()
    {
        $consoleScript = $this->createMock(ConsoleScript::class);
        $consoleScript->method('__invoke')->with('js/form_clientconfig.js')->willReturn('<scriptClientConfig>');

        $form = $this->createStub(ClientConfigForm::class);

        /** @psalm-suppress InvalidArgument (Mockery bug) */
        $helper = Mockery::mock(ClientConfigHelper::class, [$consoleScript])->makePartial();
        $helper->shouldReceive('renderForm')->with($form)->andReturn('rendered form');

        $this->assertEquals('rendered form<scriptClientConfig>', $helper->render($form));
    }

    public function testRenderContent()
    {
        $fieldset = $this->createStub(Fieldset::class);
        $element = $this->createStub(Element::class);
        $object = $this->createStub(ClientOrGroup::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->with('formRow', [$element])->willReturn('Element');

        $iterator = new PriorityList();
        $iterator->insert('element', $element, 1);
        $iterator->insert('fieldset', $fieldset, 0);

        $form = $this->createStub(ClientConfigForm::class);
        $form->method('getClientObject')->willReturn($object);
        $form->method('getIterator')->willReturn($iterator);

        $helper = $this->createPartialMock(ClientConfigHelper::class, ['getView', 'renderFieldset']);
        $helper->method('getView')->willReturn($view);
        $helper->method('renderFieldset')->with($fieldset, $object)->willReturn('Fieldset');

        $this->assertEquals('ElementFieldset', $helper->renderContent($form));
    }

    public static function renderFieldsetProvider()
    {
        return [
            [
                Group::class,
                '(DEFAULT: YES)',
                '(DEFAULT: NO)',
                '(DEFAULT: DEFAULT_TEXT)',
            ],
            [
                Client::class,
                '(DEFAULT: YES, EFFECTIVE: NO)',
                '(DEFAULT: NO, EFFECTIVE: YES)',
                '(DEFAULT: DEFAULT_TEXT, EFFECTIVE: effective_text)',
            ],
        ];
    }

    /** @dataProvider renderFieldsetProvider */
    public function testRenderFieldset(string $class, string $infoTrue, string $infoFalse, string $infoText)
    {
        $elements = "(LABEL_TRUE)<span>(ELEMENT_TRUE)(INFO_TRUE)</span>(ERRORS_TRUE)\n" .
            "(LABEL_FALSE)<span>(ELEMENT_FALSE)(INFO_FALSE)</span>(ERRORS_FALSE)\n" .
            "(LABEL_TEXT)<span>(ELEMENT_TEXT)(INFO_TEXT)</span>(ERRORS_TEXT)\n" .
            "(LABEL_NOINFO)<span>(ELEMENT_NOINFO)</span>(ERRORS_NOINFO)\n";

        /** @var MockObject|ClientOrGroup */
        $object = $this->createMock($class);
        $object->expects($this->exactly(3))->method('getDefaultConfig')->willReturnMap([
            ['default_true', 1],
            ['default_false', 0],
            ['text', 'DEFAULT_TEXT'],
        ]);
        if ($class == Client::class) {
            $object->method('getEffectiveConfig')->willReturnMap([
                ['default_true', 0],
                ['default_false', 1],
                ['text', 'effective_text'],
            ]);
        }

        $elementDisabled = $this->createMock('Laminas\Form\Element');
        $elementDisabled->method('getAttribute')->with('disabled')->willReturn(true);
        $elementDisabled->expects($this->never())->method('getName');

        $elementCheckboxDefaultTrue = $this->createMock('Laminas\Form\Element\Checkbox');
        $elementCheckboxDefaultTrue->method('getAttribute')->with('disabled')->willReturn(false);
        $elementCheckboxDefaultTrue->method('getName')->willReturn('Scan[default_true]');

        $elementCheckboxDefaultFalse = $this->createMock('Laminas\Form\Element\Checkbox');
        $elementCheckboxDefaultFalse->method('getAttribute')->with('disabled')->willReturn(false);
        $elementCheckboxDefaultFalse->method('getName')->willReturn('Scan[default_false]');

        $elementText = $this->createMock('Laminas\Form\Element\Text');
        $elementText->method('getAttribute')->with('disabled')->willReturn(false);
        $elementText->method('getName')->willReturn('Scan[text]');

        $elementWithoutInfo = $this->createMock('Laminas\Form\Element\Select');
        $elementWithoutInfo->method('getAttribute')->with('disabled')->willReturn(false);
        $elementWithoutInfo->method('getName')->willReturn('Scan[scanThisNetwork]');

        $iterator = new PriorityList();
        $iterator->insert('disabled', $elementDisabled, 4);
        $iterator->insert('true', $elementCheckboxDefaultTrue, 3);
        $iterator->insert('false', $elementCheckboxDefaultFalse, 2);
        $iterator->insert('text', $elementText, 1);
        $iterator->insert('noinfo', $elementWithoutInfo, 0);

        /** @var Stub|Fieldset */
        $fieldset = $this->createStub(Fieldset::class);
        $fieldset->method('getIterator')->willReturn($iterator);

        /** @var MockObject|FormFieldset */
        $fieldsetHelper = $this->createMock(FormFieldset::class);
        $fieldsetHelper->method('renderFieldsetElement')->with($fieldset, $elements)->willReturn('FIELDSET');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')->willReturnMap(
            array(
                array('translate', array('Default'), 'DEFAULT'),
                array('translate', array('Effective'), 'EFFECTIVE'),
                array('translate', array('Yes'), 'YES'),
                array('translate', array('No'), 'NO'),
                array('formLabel', array($elementCheckboxDefaultTrue), '(LABEL_TRUE)'),
                array('formLabel', array($elementCheckboxDefaultFalse), '(LABEL_FALSE)'),
                array('formLabel', array($elementText), '(LABEL_TEXT)'),
                array('formLabel', array($elementWithoutInfo), '(LABEL_NOINFO)'),
                array('formElement', array($elementCheckboxDefaultTrue), '(ELEMENT_TRUE)'),
                array('formElement', array($elementCheckboxDefaultFalse), '(ELEMENT_FALSE)'),
                array('formElement', array($elementText), '(ELEMENT_TEXT)'),
                array('formElement', array($elementWithoutInfo), '(ELEMENT_NOINFO)'),
                array('formElementErrors', array($elementCheckboxDefaultTrue), "(ERRORS_TRUE)\n"),
                array('formElementErrors', array($elementCheckboxDefaultFalse), "(ERRORS_FALSE)\n"),
                array('formElementErrors', array($elementText), "(ERRORS_TEXT)\n"),
                array('formElementErrors', array($elementWithoutInfo), "(ERRORS_NOINFO)\n"),
                array('escapeHtml', array($infoTrue), '(INFO_TRUE)'),
                array('escapeHtml', array($infoFalse), '(INFO_FALSE)'),
                array('escapeHtml', array($infoText), '(INFO_TEXT)'),
            )
        );
        $view->method('plugin')->with('consoleFormFieldset')->willReturn($fieldsetHelper);

        $helper = $this->createPartialMock(ClientConfigHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('FIELDSET', $helper->renderFieldset($fieldset, $object));
    }
}
