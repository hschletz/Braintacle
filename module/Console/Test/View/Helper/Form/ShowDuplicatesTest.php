<?php
/**
 * Tests for the ShowDuplicates Helper
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

class ShowDuplicatesTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function _getHelperName()
    {
        return 'consoleFormShowDuplicates';
    }

    public function testRenderElements()
    {
        $date1 = $this->createMock('DateTime');
        $date2 = $this->createMock('DateTime');

        $checkbox1 = $this->createMock('\Laminas\Form\ElementInterface');
        $checkbox2 = $this->createMock('\Laminas\Form\ElementInterface');

        $consoleUrl = $this->createMock('Library\View\Helper\HtmlElement');
        $consoleUrl->method('__invoke')
                   ->withConsecutive(
                       ['client', 'customfields', ['id' => 1]],
                       ['client', 'customfields', ['id' => 2]]
                   )->willReturnOnConsecutiveCalls('url1', 'url2');

        $dateFormat = $this->createMock('Laminas\I18n\View\Helper\DateFormat');
        $dateFormat->method('__invoke')
                   ->withConsecutive([$this->identicalTo($date1)], [$this->identicalTo($date2)])
                   ->willReturnOnConsecutiveCalls('date1_formatted', 'date2_formatted');

        $escapeHtml = $this->createMock('Laminas\View\Helper\EscapeHtml');
        $escapeHtml->method('__invoke')
                   ->willReturnCallback(function ($value) {
                       return $value . '_escaped';
                   });

        $formRow = $this->createMock('Laminas\Form\View\Helper\FormRow');
        $formRow->method('__invoke')
                ->withConsecutive(
                    [$this->identicalTo($checkbox1), \Laminas\Form\View\Helper\FormRow::LABEL_APPEND],
                    [$this->identicalTo($checkbox2), \Laminas\Form\View\Helper\FormRow::LABEL_APPEND]
                )->willReturnOnConsecutiveCalls('<checkbox1>', '<checkbox2>');

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->method('__invoke')
                    ->withConsecutive(
                        ['a', 'name1_escaped', ['href' => 'url1'], true],
                        ['a', 'name2_escaped', ['href' => 'url2'], true]
                    )->willReturnOnConsecutiveCalls('link1', 'link2');

        $table = $this->createMock('Console\View\Helper\Table');
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

        $translate = $this->createMock('Laminas\I18n\View\Helper\Translate');
        $translate->method('__invoke')->willReturnCallback(function ($message) {
            return $message . '_translated';
        });

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');

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

        $form = $this->createMock('Console\Form\ShowDuplicates');
        $form->method('getOption')->willReturnMap([
            ['order', '_order'],
            ['direction', '_direction'],
            ['clients', [$client1, $client2]]
        ]);
        $form->method('getIterator')->willReturn(new \ArrayIterator([$checkbox1, $checkbox2]));

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(['getBlacklistLink', 'getView'])
                       ->getMock();
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
            $helper->renderElements($form)
        );
    }

    public function testGetBlacklistLinkNullValue()
    {
        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['getBlacklistLink'])
                       ->getMock();

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

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(['getView'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('link', $helper->getBlacklistLink('Property', 'property_value'));
    }
}
