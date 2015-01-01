<?php
/**
 * Tests for the Table helper
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
     * HtmlTag mock
     * @var \Library\View\Helper\HtmlTag
     */
    protected $_htmlTag;

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

    /**
     * Expected result for $_headers/$_data
     */
    protected $_expected = "<table class='alternating'>\nHEADER1|HEADER2\nvalue1a|value2a\nvalue1b|value2b\n</table>\n";

    /**
     * Collection of data rendered by renderCallback()
     * @var array
     */
    protected $_renderCallbackData;

    /**
     * Mock for row()
     *
     * This is a simplified row renderer. Columns are separated by "|". Rows are
     * terminated by "\n". Headers are converted uppercase.
     *
     * @param string[] $columns
     * @param bool $isHeader
     * @return string
     */
    public function mockRow(array $columns, $isHeader)
    {
        if ($isHeader) {
            $columns = array_map('strtoupper', $columns);
        }
        return implode('|', $columns) . "\n";
    }

    /**
     * Sample callback for cell rendering
     *
     * Cell data is returned unchanged, but also appended to
     * $_renderCallbackData which can be evaluated after the table is rendered.
     *
     * @param \Zend\View\Renderer\RendererInterface $view
     * @param array $rowData
     * @param string $key
     * @return mixed
     */
    public function renderCallback(\Zend\View\Renderer\RendererInterface $view, array $rowData, $key)
    {
        $this->_renderCallbackData[] = $rowData[$key];
        return $rowData[$key];
    }

    public function setUp()
    {
        $this->_escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $this->_htmlTag = $this->getMockBuilder('Library\View\Helper\HtmlTag')
                               ->disableOriginalConstructor()
                               ->getMock();
        $this->_consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->_dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        parent::setUp();
    }

    public function testInvokeNoData()
    {
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $this->assertEquals('', $table(array(), $this->_headers));
    }

    public function testInvokeBasic()
    {
        $this->_escapeHtml->expects($this->exactly(4)) // once per non-header cell
                          ->method('__invoke')
                          ->will($this->returnArgument(0));
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $table->expects($this->never()) // No sortable headers in this test
              ->method('sortableHeader');
        $table->expects($this->exactly(3))
              ->method('row')
              ->will($this->returnCallback(array($this, 'mockRow')));

        $this->assertEquals($this->_expected, $table($this->_data, $this->_headers));
    }

    public function testInvokeWithSortablHeaders()
    {
        // The row() invocations are tested explicitly because the passed keys are significant.
        $this->_escapeHtml->expects($this->exactly(4)) // once per non-header cell
                          ->method('__invoke')
                          ->will($this->returnArgument(0));
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $table->expects($this->exactly(2)) // once per column
              ->method('sortableHeader')
              ->will($this->returnArgument(0));
        $table->expects($this->at(2))
              ->method('row')
              ->with($this->_headers, true)
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(3))
              ->method('row')
              ->with(array('column1' => 'value1a', 'column2' => 'value2a'), false)
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(4))
              ->method('row')
              ->with(array('column1' => 'value1b', 'column2' => 'value2b'), false)
              ->will($this->returnCallback(array($this, 'mockRow')));

        $this->assertEquals(
            $this->_expected,
            $table(
                $this->_data,
                $this->_headers,
                array('order' => 'column1', 'direction' => 'asc')
            )
        );
    }

    public function testInvokeWithRenderCallback()
    {
        // Test with render callback on column2.
        $this->_escapeHtml->expects($this->exactly(2)) // once per non-header cell that is not rendered via callback
                          ->method('__invoke')
                          ->will($this->returnArgument(0));
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $table->expects($this->never()) // No sortable headers in this test
              ->method('sortableHeader');
        $table->expects($this->exactly(3))
              ->method('row')
              ->with($this->anything(), $this->anything(), array())
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->setView(\Library\Application::getService('ViewManager')->getRenderer());

        $this->_renderCallbackData = array();
        $this->assertEquals(
            $this->_expected,
            $table(
                $this->_data,
                $this->_headers,
                array(),
                array('column2' => array($this, 'renderCallback'))
            )
        );
        $this->assertEquals(array('value2a', 'value2b'), $this->_renderCallbackData);
    }

    public function testInvokeWithColumnClasses()
    {
        // Test with column class set on column 2. The row() invocations are
        // tested explicitly because the passed keys are significant.
        $columnClasses = array('column2' => 'test');
        $this->_escapeHtml->expects($this->exactly(4)) // once per non-header cell
                          ->method('__invoke')
                          ->will($this->returnArgument(0));
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $table->expects($this->at(0))
              ->method('row')
              ->with($this->_headers, true, $columnClasses)
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(1))
              ->method('row')
              ->with(array('column1' => 'value1a', 'column2' => 'value2a'), false, $columnClasses)
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(2))
              ->method('row')
              ->with(array('column1' => 'value1b', 'column2' => 'value2b'), false, $columnClasses)
              ->will($this->returnCallback(array($this, 'mockRow')));

        $this->assertEquals($this->_expected, $table($this->_data, $this->_headers, array(), array(), $columnClasses));
    }

    public function testInvokeWithRowClassCallback()
    {
        $rowClassCallback = function($columns) {
            static $counter = 0;
            if ($counter++) {
                return "$columns[column1]+$columns[column2]";
            } else {
                return '';
            }
        };
        $this->_escapeHtml->expects($this->exactly(4)) // once per non-header cell that is not rendered via callback
                          ->method('__invoke')
                          ->will($this->returnArgument(0));
        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(
                          array($this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat)
                      )
                      ->setMethods(array('sortableHeader', 'row'))
                      ->getMock();
        $table->expects($this->never()) // No sortable headers in this test
              ->method('sortableHeader');
        $table->expects($this->at(0))
              ->method('row')
              ->with($this->_headers, true, array(), null)
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(1))
              ->method('row')
              ->with(array('column1' => 'value1a', 'column2' => 'value2a'), false, array(), '')
              ->will($this->returnCallback(array($this, 'mockRow')));
        $table->expects($this->at(2))
              ->method('row')
              ->with(array('column1' => 'value1b', 'column2' => 'value2b'), false, array(), 'value1b+value2b')
              ->will($this->returnCallback(array($this, 'mockRow')));

        $this->assertEquals(
            $this->_expected,
            $table(
                $this->_data,
                $this->_headers,
                array(),
                array(),
                array(),
                $rowClassCallback
            )
        );
    }

    /**
     * Test rendering of \Zend_Date objects
     */
    public function testDateFormat()
    {
        $date = new \Zend_Date(1388567012);
        $data = array(
            array(1388567012, $date),
            array($date, $date),
            array($date, 1388567012),
        );
        $this->_dateFormat->expects($this->exactly(2)) // column 0 should be rendered by callback
                          ->method('__invoke')
                          ->with(1388567012, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $callback = function() {
        };
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat
        );
        $helper(
            $data,
            array('col1', 'col2'),
            array(),
            array(0 => $callback)
        );
    }

    public function testSortableHeaderAscending()
    {
        // Arrow indicator up, link sorts descending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'desc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlTag->expects($this->once())
                       ->method('__invoke')
                       ->with('a', 'Label&uarr;', array('href' => 'ConsoleUrlMock'))
                       ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat
        );
        $this->assertEquals('HtmlTagMock', $helper->sortableHeader('Label', 'Key', 'Key', 'asc'));
    }

    public function testSortableHeaderDescending()
    {
        // Arrow indicator down, link sorts ascending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlTag->expects($this->once())
                       ->method('__invoke')
                       ->with('a', 'Label&darr;', array('href' => 'ConsoleUrlMock'))
                       ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat
        );
        $this->assertEquals('HtmlTagMock', $helper->sortableHeader('Label', 'Key', 'Key', 'desc'));
    }

    public function testSortableHeaderNoSort()
    {
        // No arrow indicator, link sorts ascending
        $this->_consoleUrl->expects($this->once())
                          ->method('__invoke')
                          ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                          ->will($this->returnValue('ConsoleUrlMock'));
        $this->_htmlTag->expects($this->once())
                       ->method('__invoke')
                       ->with('a', 'Label', array('href' => 'ConsoleUrlMock'))
                       ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table(
            $this->_escapeHtml, $this->_htmlTag, $this->_consoleUrl, $this->_dateFormat
        );
        $this->assertEquals('HtmlTagMock', $helper->sortableHeader('Label', 'Key', 'Order', 'desc'));
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
