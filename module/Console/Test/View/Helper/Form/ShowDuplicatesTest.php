<?php

/**
 * Tests for the ShowDuplicates Helper
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

use Console\Form\ShowDuplicates as ShowDuplicatesForm;
use Console\View\Helper\ConsoleUrl;
use Console\View\Helper\Form\ShowDuplicates as ShowDuplicatesHelper;
use Console\View\Helper\Table;
use DateTime;
use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormRow;
use Laminas\I18n\View\Helper\DateFormat;
use Laminas\I18n\View\Helper\Translate;
use Laminas\Stdlib\PriorityList;
use Laminas\View\Helper\EscapeHtml;
use Laminas\View\Renderer\PhpRenderer;
use Library\View\Helper\HtmlElement;

class ShowDuplicatesTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function getHelperName()
    {
        return 'consoleFormShowDuplicates';
    }

    public function testRender()
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->expects($this->once())->method('__call')->with('consoleScript', ['form_showduplicates.js']);

        $form = $this->createStub(ShowDuplicatesForm::class);

        $helper = $this->createPartialMock(ShowDuplicatesHelper::class, ['getView', 'renderForm']);
        $helper->method('getView')->willReturn($view);
        $helper->method('renderForm')->with($form)->willReturn('rendered form');

        $this->assertEquals('rendered form', $helper->render($form));
    }

    public function testRenderContent()
    {
        $date1 = $this->createStub(DateTime::class);
        $date2 = $this->createStub(DateTime::class);

        $checkbox1 = $this->createStub(ElementInterface::class);
        $checkbox2 = $this->createStub(ElementInterface::class);

        $consoleUrl = $this->createMock(ConsoleUrl::class);
        $consoleUrl->method('__invoke')
                   ->withConsecutive(
                       ['client', 'customfields', ['id' => 1]],
                       ['client', 'customfields', ['id' => 2]]
                   )->willReturnOnConsecutiveCalls('url1', 'url2');

        $dateFormat = $this->createMock(DateFormat::class);
        $dateFormat->method('__invoke')
                   ->withConsecutive([$this->identicalTo($date1)], [$this->identicalTo($date2)])
                   ->willReturnOnConsecutiveCalls('date1_formatted', 'date2_formatted');

        $escapeHtml = $this->createMock(EscapeHtml::class);
        $escapeHtml->method('__invoke')
                   ->willReturnCallback(function ($value) {
                       return $value . '_escaped';
                   });

        $formRow = $this->createMock(FormRow::class);
        $formRow->method('__invoke')
                ->withConsecutive(
                    [$this->identicalTo($checkbox1), FormRow::LABEL_APPEND],
                    [$this->identicalTo($checkbox2), FormRow::LABEL_APPEND]
                )->willReturnOnConsecutiveCalls('<checkbox1>', '<checkbox2>');

        $htmlElement = $this->createMock(HtmlElement::class);
        $htmlElement->method('__invoke')
                    ->withConsecutive(
                        ['a', 'name1_escaped', ['href' => 'url1'], true],
                        ['a', 'name2_escaped', ['href' => 'url2'], true]
                    )->willReturnOnConsecutiveCalls('link1', 'link2');

        $table = $this->createMock(Table::class);
        $table->method('prepareHeaders')
              ->with(
                  [
                    'Id' => 'ID',
                    'Name' => 'Name_translated',
                    'NetworkInterface.MacAddress' => 'MAC address_translated',
                    'Serial' => 'Serial number_translated',
                    'AssetTag' => 'Asset tag_translated',
                    'LastContactDate' => 'Last contact_translated',
                  ],
                  [
                      'order' => '_order',
                      'direction' => '_direction',
                  ]
              )->willReturn(['Id' => 'ID', 'others' => '']);
        $table->method('row')
              ->withConsecutive(
                  [
                    [
                        'Id' => '<input type="checkbox" class="checkAll">ID',
                        'others' => '',
                    ],
                    true,
                    [],
                    null,
                  ],
                  [
                    [
                        '<input type="checkbox" name="clients[]" value="1">1',
                        'link1',
                        'blacklist_macaddress1',
                        'blacklist_serial1',
                        'blacklist_assettag1',
                        'date1_formatted_escaped'
                    ],
                    false,
                    [],
                    null
                  ],
                  [
                    [
                        '<input type="checkbox" name="clients[]" value="2">2',
                        'link2',
                        'blacklist_macaddress2',
                        'blacklist_serial2',
                        'blacklist_assettag2',
                        'date2_formatted_escaped'
                    ],
                    false,
                    [],
                    null
                  ]
              )
              ->willReturnOnConsecutiveCalls('<header>', '<row1>', '<row2>');
        $table->method('tag')->with('<header><row1><row2>')->willReturn('<duplicates_table>');

        $translate = $this->createStub(Translate::class);
        $translate->method('__invoke')->willReturnCallback(function ($message) {
            return $message . '_translated';
        });

        $view = $this->createStub(PhpRenderer::class);

        $view->method('plugin')->willReturnMap([
            ['consoleUrl', null, $consoleUrl],
            ['dateFormat', null, $dateFormat],
            ['escapeHtml', null, $escapeHtml],
            ['formRow', null, $formRow],
            ['htmlElement', null, $htmlElement],
            ['table', null, $table],
            ['translate', null, $translate],
        ]);

        $client1 = [
            'Id' => 1,
            'Name' => 'name1',
            'NetworkInterface.MacAddress' => 'macaddress1',
            'Serial' => 'serial1',
            'AssetTag' => 'assettag1',
            'LastContactDate' => $date1,
        ];
        $client2 = [
            'Id' => 2,
            'Name' => 'name2',
            'NetworkInterface.MacAddress' => 'macaddress2',
            'Serial' => 'serial2',
            'AssetTag' => 'assettag2',
            'LastContactDate' => $date2,
        ];

        $iterator = new PriorityList();
        $iterator->insert('1', $checkbox1, 1);
        $iterator->insert('2', $checkbox2, 0);

        $form = $this->createStub(ShowDuplicatesForm::class);
        $form->method('getOption')->willReturnMap([
            ['order', '_order'],
            ['direction', '_direction'],
            ['clients', [$client1, $client2]]
        ]);
        $form->method('getIterator')->willReturn($iterator);

        $helper = $this->createPartialMock(ShowDuplicatesHelper::class, ['getBlacklistLink', 'getView']);
        $helper->method('getView')->willReturn($view);
        $helper->method('getBlacklistLink')->willReturnMap([
            ['MacAddress', 'macaddress1', 'blacklist_macaddress1'],
            ['Serial', 'serial1', 'blacklist_serial1'],
            ['AssetTag', 'assettag1', 'blacklist_assettag1'],
            ['MacAddress', 'macaddress2', 'blacklist_macaddress2'],
            ['Serial', 'serial2', 'blacklist_serial2'],
            ['AssetTag', 'assettag2', 'blacklist_assettag2'],
        ]);

        $this->assertEquals(
            '<duplicates_table><checkbox1><checkbox2>',
            $helper->renderContent($form)
        );
    }

    public function testGetBlacklistLinkNullValue()
    {
        $helper = new ShowDuplicatesHelper();
        $this->assertSame('', $helper->getBlacklistLink('Property', null));
    }

    public function testGetBlacklistLinkNonNullValue()
    {
        $consoleUrl = $this->createMock('Library\View\Helper\HtmlElement');
        $consoleUrl->method('__invoke')
                   ->with('duplicates', 'allow', ['criteria' => 'Property', 'value' => 'property_value'])
                   ->willReturn('url');

        $escapeHtml = $this->createMock('Laminas\View\Helper\EscapeHtml');
        $escapeHtml->method('__invoke')->with('property_value')->willReturn('value_escaped');

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->method('__invoke')->with('a', 'value_escaped', ['href' => 'url'], true)->willReturn('link');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('plugin')->willReturnMap([
            ['consoleUrl', null, $consoleUrl],
            ['escapeHtml', null, $escapeHtml],
            ['htmlElement', null, $htmlElement],
        ]);

        $helper = $this->createPartialMock(ShowDuplicatesHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('link', $helper->getBlacklistLink('Property', 'property_value'));
    }
}
