<?php
/**
 * Tests for the Table helper
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\View\Helper;

/**
 * Tests for the Table helper
 */
class TableTest extends \Library\Test\View\Helper\AbstractTest
{
    /**
     * EscapeHtml mock
     * @var \Zend\View\Helper\EscapeHtml
     */
    protected $_escapeHtml;

    /**
     * HtmlElement mock
     * @var \Library\View\Helper\HtmlElement
     */
    protected $_htmlElement;

    /**
     * ConsoleUrl mock
     * @var \Console\View\Helper\ConsoleUrl
     */
    protected $_consoleUrl;

    /**
     * DateFormat mock
     * @var \Zend\I18n\View\Helper\DateFormat
     */
    protected $_dateFormat;

    /**
     * Sample header data
     * @var array
     */
    protected $_headers = array(
        'column1' => 'header1',
        'column2' => 'header2',
    );

    /**
     * Sample body data
     * @var array
     */
    protected $_data = array(
        array(
            'column1' => 'value1a',
            'column2' => 'value2a',
            'unused' => 'value', // Extra column, should not be evaluated
        ),
        array(
            // Test different order
            'column2' => 'value2b',
            'column1' => 'value1b',
        ),
    );

    public function setUp()
    {
        $this->_escapeHtml = $this->createMock('Zend\View\Helper\EscapeHtml');
        $this->_htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $this->_consoleUrl = $this->createMock('Console\View\Helper\ConsoleUrl');
        $this->_dateFormat = $this->createMock('Zend\I18n\View\Helper\DateFormat');
        parent::setUp();
    }

    public function testInvokeNoData()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods()
                      ->getMock();

        $this->assertEquals('', $table([], $this->_headers));
    }

    public function testInvokeWithDefaultParams()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['headerRow', 'dataRows', 'tag'])
                      ->getMock();

        $table->method('headerRow')->with($this->_headers, [], [])->willReturn('<header>');
        $table->method('dataRows')->with($this->_data, ['column1', 'column2'], [], [], null)->willReturn('<data>');
        $table->method('tag')->with('<header><data>')->willReturn('table_tag');
        $this->assertEquals('table_tag', $table($this->_data, $this->_headers));
    }

    public function testInvokeWithExplicitParams()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['headerRow', 'dataRows', 'tag'])
                      ->getMock();

        $table->method('headerRow')->with($this->_headers, ['sorting'], ['columnClasses'])->willReturn('<header>');
        $table->method('dataRows')->with(
            $this->_data,
            ['column1', 'column2'],
            ['renderCallbacks'],
            ['columnClasses'],
            'rowClassCallback'
        )->willReturn('<data>');
        $table->method('tag')->with('<header><data>')->willReturn('table_tag');

        $this->assertEquals(
            'table_tag',
            $table(
                $this->_data,
                $this->_headers,
                ['sorting'],
                ['renderCallbacks'],
                ['columnClasses'],
                'rowClassCallback'
            )
        );
    }

    public function testTag()
    {
        $this->_htmlElement->method('__invoke')
                           ->with('table', 'table_content', ['class' => 'alternating'])
                           ->willReturn('table_tag');

        $class = static::_getHelperClass();
        $table = new $class($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat);

        $this->assertEquals('table_tag', $table->tag('table_content'));
    }

    public function testHeaderRowWithDefaultParams()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['prepareHeaders', 'row'])
                      ->getMock();

        $table->method('prepareHeaders')->with($this->_headers, [])->willReturn(['headers']);
        $table->method('row')->with(['headers'], true, [])->willReturn('header_row');

        $this->assertEquals('header_row', $table->headerRow($this->_headers));
    }

    public function testHeaderRowWithExplicitParams()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['prepareHeaders', 'row'])
                      ->getMock();

        $table->method('prepareHeaders')->with($this->_headers, ['sorting'])->willReturn(['headers']);
        $table->method('row')->with(['headers'], true, ['classes'])->willReturn('header_row');

        $this->assertEquals('header_row', $table->headerRow($this->_headers, ['sorting'], ['classes']));
    }

    public function testDataRowsWithDefaultParams()
    {
        $this->_escapeHtml->method('__invoke')->willReturnOnConsecutiveCalls('1a', '2a', '1b', '2b');

        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(['row'])
                      ->getMock();

        $table->method('row')
              ->withConsecutive(
                  [['column1' => '1a', 'column2' => '2a'], false, [], null],
                  [['column1' => '1b', 'column2' => '2b'], false, [], null]
              )
              ->willReturnOnConsecutiveCalls('<row1>', '<row2>');

        $this->assertEquals('<row1><row2>', $table->dataRows($this->_data, ['column1', 'column2']));
    }

    public function testDataRowsWithColumnClasses()
    {
        $this->_escapeHtml->method('__invoke')->willReturnOnConsecutiveCalls('1a', '2a', '1b', '2b');

        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(['row'])
                      ->getMock();

        $table->method('row')
              ->withConsecutive(
                  [['column1' => '1a', 'column2' => '2a'], false, ['column1' => 'class'], null],
                  [['column1' => '1b', 'column2' => '2b'], false, ['column1' => 'class'], null]
              )
              ->willReturnOnConsecutiveCalls('<row1>', '<row2>');

        $this->assertEquals(
            '<row1><row2>',
            $table->dataRows($this->_data, ['column1', 'column2'], [], ['column1' => 'class'])
        );
    }

    public function testDataRowsWithRowClassCallback()
    {
        $this->_escapeHtml->method('__invoke')->willReturnOnConsecutiveCalls('1a', '2a', '1b', '2b');

        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(['row'])
                      ->getMock();

        $table->method('row')
              ->withConsecutive(
                  [['column1' => '1a', 'column2' => '2a'], false, [], 'VALUE1A'],
                  [['column1' => '1b', 'column2' => '2b'], false, [], 'VALUE1B']
              )
              ->willReturnOnConsecutiveCalls('<row1>', '<row2>');

        $rowClassCallback = function ($rowData) {
            $this->assertContains($rowData, $this->_data);
            return strtoupper($rowData['column1']);
        };

        $this->assertEquals('<row1><row2>', $table->dataRows($this->_data, ['column1', 'column2'], [], [], $rowClassCallback));
    }

    public function testDataRowsWithDateTime()
    {
        $date = $this->createMock('DateTime');

        $this->_dateFormat->method('__invoke')
                          ->with($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                          ->willReturn('date_formatted');

        $this->_escapeHtml->method('__invoke')->with('date_formatted')->willReturn('escaped_date');

        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(['row'])
                      ->getMock();

        $table->method('row')
              ->with(['column1' => 'escaped_date'], false, [], null)
              ->willReturn('<row>');

        $this->assertEquals('<row>', $table->dataRows([['column1' => $date]], ['column1']));
    }

    public function testDataRowsWithRenderCallbackPrecedesDateTime()
    {
        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $date = $this->createMock('DateTime');

        $this->_dateFormat->expects($this->never())->method('__invoke');
        $this->_escapeHtml->expects($this->never())->method('__invoke');

        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlElement, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(['row', 'getView'])
                      ->getMock();

        $table->method('row')
              ->with(['column1' => 'callback_return'], false, [], null)
              ->willReturn('<row>');

        $table->method('getView')->willReturn($view);

        $renderCallback = function ($view2, $rowData, $key) use ($view, $date) {
            $this->assertSame($view2, $view);
            $this->assertEquals(['column1' => $date], $rowData);
            $this->assertEquals('column1', $key);
            return 'callback_return';
        };

        $this->assertEquals(
            '<row>',
            $table->dataRows([['column1' => $date]], ['column1'], ['column1' => $renderCallback])
        );
    }

    public function testPrepareHeadersWithoutSorting()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['sortableHeader'])
                      ->getMock();

        $table->expects($this->never())->method('sortableHeader');

        $this->assertEquals($this->_headers, $table->prepareHeaders($this->_headers, []));
    }

    public function testPrepareHeadersWithSorting()
    {
        $table = $this->getMockBuilder(static::_getHelperClass())
                      ->disableOriginalConstructor()
                      ->setMethods(['sortableHeader'])
                      ->getMock();

        $table->method('sortableHeader')->withConsecutive(
            ['header1', 'column1', 'column2', 'asc'],
            ['header2', 'column2', 'column2', 'asc']
        )->willReturnOnConsecutiveCalls('sort1', 'sort2');

        $this->assertEquals(
            ['column1' => 'sort1', 'column2' => 'sort2'],
            $table->prepareHeaders($this->_headers, ['order' => 'column2', 'direction' => 'asc'])
        );
    }

    public function testSortableHeaderAscending()
    {
        // Arrow indicator up, link sorts descending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'desc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlElement->expects($this->once())
                           ->method('__invoke')
                           ->with('a', 'Label&uarr;', array('href' => 'ConsoleUrlMock'))
                           ->will($this->returnValue('HtmlElementMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml,
            $this->_htmlElement,
            $this->_consoleUrl,
            $this->_dateFormat
        );
        $this->assertEquals('HtmlElementMock', $helper->sortableHeader('Label', 'Key', 'Key', 'asc'));
    }

    public function testSortableHeaderDescending()
    {
        // Arrow indicator down, link sorts ascending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlElement->expects($this->once())
                           ->method('__invoke')
                           ->with('a', 'Label&darr;', array('href' => 'ConsoleUrlMock'))
                           ->will($this->returnValue('HtmlElementMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml,
            $this->_htmlElement,
            $this->_consoleUrl,
            $this->_dateFormat
        );
        $this->assertEquals('HtmlElementMock', $helper->sortableHeader('Label', 'Key', 'Key', 'desc'));
    }

    public function testSortableHeaderNoSort()
    {
        // No arrow indicator, link sorts ascending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlElement->expects($this->once())
                           ->method('__invoke')
                           ->with('a', 'Label', array('href' => 'ConsoleUrlMock'))
                           ->will($this->returnValue('HtmlElementMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml,
            $this->_htmlElement,
            $this->_consoleUrl,
            $this->_dateFormat
        );
        $this->assertEquals('HtmlElementMock', $helper->sortableHeader('Label', 'Key', 'Order', 'desc'));
    }

    /**
     * Tests for the row() method
     */
    public function testRow()
    {
        $helper = $this->_getHelper();
        $this->assertEquals(
            "<tr>\n<td>\nheader1\n</td>\n<td>\nheader2\n</td>\n\n</tr>\n",
            $helper->row($this->_headers, false)
        );
        $this->assertEquals(
            "<tr>\n<th>\nheader1\n</th>\n<th>\nheader2\n</th>\n\n</tr>\n",
            $helper->row($this->_headers, true)
        );
        $this->assertEquals(
            "<tr>\n<th>\nheader1\n</th>\n<th class=\"test\">\nheader2\n</th>\n\n</tr>\n",
            $helper->row($this->_headers, true, array('column2' => 'test'), '')
        );
        $this->assertEquals(
            "<tr class=\"row\">\n<th>\nheader1\n</th>\n<th>\nheader2\n</th>\n\n</tr>\n",
            $helper->row($this->_headers, true, array(), 'row')
        );
    }
}
