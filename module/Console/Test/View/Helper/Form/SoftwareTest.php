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

use Console\Form\Software as SoftwareForm;
use Console\View\Helper\Form\Software as SoftwareHelper;
use Console\View\Helper\Table;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Fieldset;
use Laminas\Form\View\Helper\FormRow;
use Laminas\I18n\View\Helper\Translate;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class SoftwareTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function getHelperName()
    {
        return 'consoleFormSoftware';
    }

    public function testInvoke()
    {
        $software = [['software']];
        $sorting = ['sorting'];
        $filter = 'all';

        $view = $this->createMock(PhpRenderer::class);
        $view->expects($this->once())->method('__call')->with('consoleScript', ['form_software.js']);

        $form = $this->createStub(SoftwareForm::class);

        /** @var MockObject|SoftwareHelper|callable */
        $helper = $this->createPartialMock(SoftwareHelper::class, ['getView', 'renderForm']);
        $helper->method('getView')->willReturn($view);
        $helper->method('renderForm')->with($form, $software, $sorting, $filter)->willReturn('rendered form');

        $this->assertEquals('rendered form', $helper($form, $software, $sorting, $filter));
    }

    public function testRenderContent()
    {
        $software = [['software']];
        $sorting = ['sorting'];
        $filter = 'all';

        $csrf = $this->createStub(Csrf::class);

        $softwareFieldset = $this->createStub(Fieldset::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->expects($this->once())->method('__call')->with('formRow', [$csrf])->willReturn('<csrf>');

        $form = $this->createMock(SoftwareForm::class);
        $form->method('get')->willReturnMap([
            ['_csrf', $csrf],
            ['Software', $softwareFieldset]
        ]);

        $helper = $this->createPartialMock(
            SoftwareHelper::class,
            ['getView', 'renderButtons', 'renderSoftwareFieldset']
        );
        $helper->method('getView')->willReturn($view);
        $helper->method('renderButtons')->with($form, $filter)->willReturn('<buttons>');
        $helper->method('renderSoftwareFieldset')
               ->with($softwareFieldset, $software, $sorting)
               ->willReturn('<fieldset>');

        $this->assertEquals(
            '<csrf><buttons><fieldset>',
            $helper->renderContent($form, $software, $sorting, $filter)
        );
    }

    public function renderButtonsProvider()
    {
        return [
            ['accepted', "\nIGNORE"],
            ['ignored', "ACCEPT\n"],
            ['', "ACCEPT\nIGNORE"],
        ];
    }

    /** @dataProvider renderButtonsProvider */
    public function testRenderButtons($filter, $buttons)
    {
        $accept = $this->createMock('Laminas\Form\ElementInterface');
        $ignore = $this->createMock('Laminas\Form\ElementInterface');

        /** @var Stub|SoftwareForm */
        $fieldset = $this->createStub(SoftwareForm::class);
        $fieldset->method('get')->willReturnMap([
            ['Accept', $accept],
            ['Ignore', $ignore],
        ]);

        $formRow = $this->createMock('Laminas\Form\View\Helper\FormRow');
        $formRow->method('__invoke')->willReturnMap([
            [$accept, null, null, null, 'ACCEPT'],
            [$ignore, null, null, null, 'IGNORE'],
        ]);

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('plugin')->with('formRow')->willReturn($formRow);

        $helper = $this->createPartialMock(SoftwareHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            "<div class='textcenter'>\n$buttons</div>\n",
            $helper->renderButtons($fieldset, $filter)
        );
    }

    public function testRenderSoftwareFieldset()
    {
        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');

        $checkbox1 = $this->createMock('\Laminas\Form\ElementInterface');
        $checkbox2 = $this->createMock('\Laminas\Form\ElementInterface');

        /** @var MockObject|SoftwareForm */
        $fieldset = $this->createMock(SoftwareForm::class);
        $fieldset->method('get')
                 ->withConsecutive(
                     ['_c29mdHdhcmVfbmFtZTE='], // 'software_name1'
                     ['_c29mdHdhcmVfbmFtZTI='] // 'software_name2'
                 )->willReturnOnConsecutiveCalls($checkbox1, $checkbox2);

        $software = [
            ['name' => 'software_name1', 'num_clients' => 2],
            ['name' => 'software_name2', 'num_clients' => 1],
        ];

        $sorting = ['order' => 'current_order', 'direction' => 'current_direction'];

        $formRow = $this->createMock('Laminas\Form\View\Helper\FormRow');
        $formRow->method('isTranslatorEnabled')->willReturn('translatorEnabled');
        $formRow->expects($this->exactly(2))
                ->method('setTranslatorEnabled')
                ->withConsecutive([false], ['translatorEnabled']);
        $formRow->expects($this->exactly(2))
                ->method('__invoke')
                ->withConsecutive(
                    [$checkbox1, FormRow::LABEL_APPEND],
                    [$checkbox2, FormRow::LABEL_APPEND]
                )
                ->willReturnOnConsecutiveCalls('checkbox1', 'checkbox2');

        $translate = $this->createMock(Translate::class);
        $translate->method('__invoke')
                  ->withConsecutive(
                      ['Name', null, null],
                      ['Count', null, null]
                  )->willReturnOnConsecutiveCalls('NAME', 'COUNT');

        $consoleUrl = $this->createMock('Library\View\Helper\HtmlElement');
        $consoleUrl->method('__invoke')
                   ->withConsecutive(
                       [
                        'client',
                        'index',
                        [
                            'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                            'jumpto' => 'software',
                            'filter' => 'Software',
                            'search' => 'software_name1',
                        ]
                       ],
                       [
                        'client',
                        'index',
                        [
                            'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                            'jumpto' => 'software',
                            'filter' => 'Software',
                            'search' => 'software_name2',
                        ]
                       ]
                   )->willReturnOnConsecutiveCalls('url1', 'url2');

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->method('__invoke')
                    ->withConsecutive(
                        ['a', 2, ['href' => 'url1']],
                        ['a', 1, ['href' => 'url2']]
                    )->willReturnOnConsecutiveCalls('link1', 'link2');

        $table = $this->createMock(Table::class);
        $table->method('prepareHeaders')
              ->with(['name' => 'NAME', 'num_clients' => 'COUNT'], $sorting)
              ->willReturn(['name' => 'header_name', 'num_clients' => 'header_count']);
        $table->method('row')
              ->withConsecutive(
                  [
                    ['name' => '<input type="checkbox" class="checkAll">header_name', 'num_clients' => 'header_count'],
                    true,
                    [],
                    null,
                  ],
                  [['name' => 'checkbox1', 'num_clients' => 'link1'], false, ['num_clients' => 'textright'], null],
                  [['name' => 'checkbox2', 'num_clients' => 'link2'], false, ['num_clients' => 'textright'], null]
              )
              ->willReturnOnConsecutiveCalls('<header>', '<row1>', '<row2>');
        $table->method('tag')->with('<header><row1><row2>')->willReturn('softwareFieldset');

        $view->method('plugin')->willReturnMap([
            ['formRow', null, $formRow],
            ['translate', null, $translate],
            ['table', null, $table],
            ['htmlElement', null, $htmlElement],
            ['consoleUrl', null, $consoleUrl],
        ]);

        $helper = $this->createPartialMock(SoftwareHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            'softwareFieldset',
            $helper->renderSoftwareFieldset($fieldset, $software, $sorting)
        );
    }
}
