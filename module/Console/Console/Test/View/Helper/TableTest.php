<?php
/**
 * Tests for the Table helper
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
     * Sample header data
     * @var array
     */
    protected $_headers = array(
        'column1' => 'header1',
        'column2' => 'header2',
    );

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

    /**
     * Tests for the __invoke() method
     */
    public function testInvoke()
    {
        // Sample table data
        $data = array(
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
        // Expected result when combined with $_headers
        $expectedTable = "<table class='alternating'>\nHEADER1|HEADER2\nvalue1a|value2a\nvalue1b|value2b\n</table>\n";

        // Stubs for row() and sortableHeader()
        $rowCallback = array($this, 'mockRow');
        $stubs = array('sortableHeader', 'row');

        // Basic test with minimal arguments
        $escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $escapeHtml->expects($this->exactly(4)) // once per non-header cell
                   ->method('__invoke')
                   ->will($this->returnArgument(0));
        $htmlTag = $this->_getHelper('HtmlTag');
        $consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')->disableOriginalConstructor()->getMock();
        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');

        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(array($escapeHtml, $htmlTag, $consoleUrl, $dateFormat))
                      ->setMethods($stubs)
                      ->getMock();
        $table->expects($this->never()) // No sortable headers in this test
              ->method('sortableHeader');
        $table->expects($this->exactly(3))
              ->method('row')
              ->will($this->returnCallback($rowCallback));

        $this->assertEquals($expectedTable, $table($data, $this->_headers));

        // Test with sortable headers
        $escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $escapeHtml->expects($this->exactly(4)) // once per non-header cell
                   ->method('__invoke')
                   ->will($this->returnArgument(0));

        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(array($escapeHtml, $htmlTag, $consoleUrl, $dateFormat))
                      ->setMethods($stubs)
                      ->getMock();
        $table->expects($this->exactly(2)) // once per column
              ->method('sortableHeader')
              ->will($this->returnArgument(0));
        $table->expects($this->exactly(3))
              ->method('row')
              ->will($this->returnCallback($rowCallback));

        $this->assertEquals(
            $expectedTable,
            $table(
                $data,
                $this->_headers,
                array('order' => 'column1', 'direction' => 'asc')
            )
        );

        // Test with render callback on column2
        $escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $escapeHtml->expects($this->exactly(2)) // once per non-header cell that is not rendered via callback
                   ->method('__invoke')
                   ->will($this->returnArgument(0));

        $table = $this->getMockBuilder($this->_getHelperClass())
                      ->setConstructorArgs(array($escapeHtml, $htmlTag, $consoleUrl, $dateFormat))
                      ->setMethods($stubs)
                      ->getMock();
        $table->expects($this->never()) // No sortable headers in this test
              ->method('sortableHeader');
        $table->expects($this->exactly(3))
              ->method('row')
              ->will($this->returnCallback($rowCallback));
        $table->setView(\Library\Application::getService('ViewManager')->getRenderer());

        $this->_renderCallbackData = array();
        $this->assertEquals(
            $expectedTable,
            $table(
                $data,
                $this->_headers,
                array(),
                array('column2' => array($this, 'renderCallback'))
            )
        );
        $this->assertEquals(array('value2a', 'value2b'), $this->_renderCallbackData);
    }

    /**
     * Test rendering of \Zend_Date objects
     */
    public function testDateFormat()
    {
        $escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $htmlTag = $this->_getHelper('HtmlTag');
        $consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')->disableOriginalConstructor()->getMock();

        $date = new \Zend_Date(1388567012);
        $data = array(
            array(1388567012, $date),
            array($date, $date),
            array($date, 1388567012),
        );
        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->exactly(2)) // column 0 should be rendered by callback
                   ->method('__invoke')
                   ->with(1388567012, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $callback = function() {
        };
        $helper = new \Console\View\Helper\Table($escapeHtml, $htmlTag, $consoleUrl, $dateFormat);
        $helper(
            $data,
            array('col1', 'col2'),
            array(),
            array(0 => $callback)
        );
    }

    /**
     * Tests for the sortableHeader() method
     */
    public function testSortableHeader()
    {
        $escapeHtml = $this->getMock('Zend\View\Helper\EscapeHtml');
        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        // Test for current column sorted ascending: Arrow indicator up, link sorts descending
        $consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')->disableOriginalConstructor()->getMock();
        $consoleUrl->expects($this->once())
                   ->method('__invoke')
                   ->with(null, null, array('order' => 'Key', 'direction' => 'desc'), true)
                   ->will($this->returnValue('ConsoleUrlMock'));
        $htmlTag = $this->getMockBuilder('Library\View\Helper\HtmlTag')->disableOriginalConstructor()->getMock();
        $htmlTag->expects($this->once())
                ->method('__invoke')
                ->with('a', 'Label&uarr;', array('href' => 'ConsoleUrlMock'))
                ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table($escapeHtml, $htmlTag, $consoleUrl, $dateFormat);
        $this->assertEquals('HtmlTagMock', $helper->sortableHeader('Label', 'Key', 'Key', 'asc'));

        // Test for current column sorted descending: Arrow indicator down, link sorts ascending
        $consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')->disableOriginalConstructor()->getMock();
        $consoleUrl->expects($this->once())
                   ->method('__invoke')
                   ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                   ->will($this->returnValue('ConsoleUrlMock'));
        $htmlTag = $this->getMockBuilder('Library\View\Helper\HtmlTag')->disableOriginalConstructor()->getMock();
        $htmlTag->expects($this->once())
                ->method('__invoke')
                ->with('a', 'Label&darr;', array('href' => 'ConsoleUrlMock'))
                ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table($escapeHtml, $htmlTag, $consoleUrl, $dateFormat);
        $this->assertEquals('HtmlTagMock', $helper->sortableHeader('Label', 'Key', 'Key', 'desc'));

        // Test for current column not sorted: No arrow indicator, link sorts ascending
        $consoleUrl = $this->getMockBuilder('Console\View\Helper\ConsoleUrl')->disableOriginalConstructor()->getMock();
        $consoleUrl->expects($this->once())
                   ->method('__invoke')
                   ->with(null, null, array('order' => 'Key', 'direction' => 'asc'), true)
                   ->will($this->returnValue('ConsoleUrlMock'));
        $htmlTag = $this->getMockBuilder('Library\View\Helper\HtmlTag')->disableOriginalConstructor()->getMock();
        $htmlTag->expects($this->once())
                ->method('__invoke')
                ->with('a', 'Label', array('href' => 'ConsoleUrlMock'))
                ->will($this->returnValue('HtmlTagMock'));
        $helper = new \Console\View\Helper\Table($escapeHtml, $htmlTag, $consoleUrl, $dateFormat);
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
    }
}
