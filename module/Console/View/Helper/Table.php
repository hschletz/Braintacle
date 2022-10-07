<?php

/**
 * Generate HTML table
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

namespace Console\View\Helper;

/**
 * Generate HTML table
 */
class Table extends \Laminas\View\Helper\AbstractHelper
{
    /**
     * EscapeHtml view helper
     * @var \Laminas\View\Helper\EscapeHtml
     */
    protected $_escapeHtml;

    /**
     * HtmlElement view helper
     * @var \Library\View\Helper\HtmlElement
     */
    protected $_htmlElement;

    /**
     * ConsoleUrl view helper
     * @var \Console\View\Helper\ConsoleUrl
     */
    protected $_consoleUrl;

    /**
     * DateFormat view helper
     * @var \Laminas\I18n\View\Helper\DateFormat
     */
    protected $_dateFormat;

    /**
     * Constructor
     *
     * @param \Laminas\View\Helper\EscapeHtml $escapeHtml
     * @param \Library\View\Helper\HtmlElement $htmlElement
     * @param \Console\View\Helper\ConsoleUrl $consoleUrl
     * @param \Laminas\I18n\View\Helper\DateFormat $dateFormat
     */
    public function __construct(
        \Laminas\View\Helper\EscapeHtml $escapeHtml,
        \Library\View\Helper\HtmlElement $htmlElement,
        \Console\View\Helper\ConsoleUrl $consoleUrl,
        \Laminas\I18n\View\Helper\DateFormat $dateFormat
    ) {
        $this->_escapeHtml = $escapeHtml;
        $this->_htmlElement = $htmlElement;
        $this->_consoleUrl = $consoleUrl;
        $this->_dateFormat = $dateFormat;
    }

    /**
     * Generate HTML table
     *
     * $headers is an associative array with header labels. Its keys are used to
     * match corresponding fields in the other arguments. For each header, a
     * corresponding field must be set in the table data or in $renderCallbacks
     * (deprecated).
     *
     * @param array|\Traversable $data see dataRows()
     * @param string[] $headers
     * @param string[] $sorting see prepareHeaders()
     * @param callable[] $renderCallbacks see dataRows()
     * @param string[] $columnClasses see row()
     * @param callable $rowClassCallback see dataRows()
     * @return string HTML table
     */
    public function __invoke(
        $data,
        array $headers,
        $sorting = array(),
        $renderCallbacks = array(),
        $columnClasses = array(),
        $rowClassCallback = null
    ) {
        if (count($data) == 0) {
            return '';
        }

        $content = $this->headerRow($headers, $sorting, $columnClasses);
        $content .= $this->dataRows($data, array_keys($headers), $renderCallbacks, $columnClasses, $rowClassCallback);

        return $this->tag($content);
    }

    /**
     * Wrap given content in "table" tag
     *
     * @param string $content
     * @return string
     */
    public function tag($content)
    {
        return $this->_htmlElement->__invoke('table', $content, ['class' => 'alternating']);
    }

    /**
     * Generate header row
     *
     * @param string[] $headers
     * @param string[] $sorting see prepareHeaders()
     * @param string[] $columnClasses see row()
     * @return string
     */
    public function headerRow($headers, $sorting = [], $columnClasses = [])
    {
        return $this->row($this->prepareHeaders($headers, $sorting), true, $columnClasses);
    }

    /**
     * Generate data rows
     *
     * $data is an array or iterator of row objects. Row objects are typically
     * associative arrays or objects implementing the \ArrayAccess interface. A
     * default rendering method is available for these types. For any other
     * type, all columns must be rendered by a callback.
     *
     * The default renderer escapes cell content automatically. \DateTime
     * objects are rendered as short timestamps (yy-mm-dd hh:mm). The
     * application's default locale controls the date/time format.
     * Alternatively, a callback can be provided in the $renderCallbacks array.
     * If a callback is defined for a column, the callback is responsible for
     * escaping cell data. It gets called with the following arguments:
     *
     * 1. The view renderer
     * 2. The row object
     * 3. The key of the column to be rendered. This is useful for callbacks
     *    that render more than 1 column.
     *
     * The render callback method is deprecated. Custom rendering should be done
     * by iterating over $data and calling row() with customized content.
     *
     * $rowClassCallback, if given, is called for each row. It receives the
     * row object from $data. Its return value is passed to row().
     *
     * @param array|\Traversable $data
     * @param string[] $keys Column keys
     * @param callable[] $renderCallbacks deprecated
     * @param string[] $columnClasses see row()
     * @param callable $rowClassCallback
     * @return string
     */
    public function dataRows($data, $keys, $renderCallbacks = [], $columnClasses = [], $rowClassCallback = null)
    {
        $rows = '';
        foreach ($data as $rowData) {
            $row = array();
            foreach ($keys as $key) {
                if (isset($renderCallbacks[$key])) {
                    $row[$key] = $renderCallbacks[$key]($this->getView(), $rowData, $key);
                } elseif ($rowData[$key] instanceof \DateTime) {
                    $row[$key] = $this->_escapeHtml->__invoke(
                        $this->_dateFormat->__invoke(
                            $rowData[$key],
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::SHORT
                        )
                    );
                } else {
                    $row[$key] = $this->_escapeHtml->__invoke($rowData[$key]);
                }
            }
            $rows .= $this->row(
                $row,
                false,
                $columnClasses,
                $rowClassCallback ? $rowClassCallback($rowData) : null
            );
        }
        return $rows;
    }

    /**
     * Apply hyperlinks to headers
     *
     * If $sorting contains the "order" and "direction" elements (other elements
     * are ignored), header values are replaced with sortableHeader() output.
     * $headers must contain the column names as keys in that case.
     *
     * @param string[] $headers
     * @param string[] $sorting
     * @return string[]
     */
    public function prepareHeaders($headers, $sorting)
    {
        if (isset($sorting['order']) and isset($sorting['direction'])) {
            foreach ($headers as $key => &$label) {
                $label = $this->sortableHeader($label, $key, $sorting['order'], $sorting['direction']);
            }
        }
        return $headers;
    }

    /**
     * Generate a header hyperlink
     *
     * The link URL points to the current action with the "order" and
     * "direction" query parameters set accordingly. The action should evaluate
     * these parameters, sort the data and provide the sorting to the view
     * renderer. The \Console\Mvc\Controller\Plugin\GetOrder controller plugin
     * simplifies these tasks.
     *
     * @param string $label Header text. An arrow will be added to the currently sorted column.
     * @param string $key Sort key to be used in the URL
     * @param string $order Current order
     * @param string $direction Current direction
     * @return string HTML Hyperlink
     */
    public function sortableHeader($label, $key, $order, $direction)
    {
        if ($key == $order) {
            // add arrow indicator to currently sorted column and
            // invert direction for the hyperlink.
            if ($direction == 'asc') {
                $linkDirection = 'desc';
                $label .= '&uarr;';
            } else {
                $linkDirection = 'asc';
                $label .= '&darr;';
            }
        } else {
            // use ascending ordering for every other hyperlink.
            $linkDirection = 'asc';
        }

        $params = array(
            'order' => $key,
            'direction' => $linkDirection
        );
        return $this->_htmlElement->__invoke(
            'a',
            $label,
            array('href' => $this->_consoleUrl->__invoke(null, null, $params, true)),
            true
        );
    }

    /**
     * Generate a table row
     *
     * @param array $columns Column data
     * @param bool $isHeader Use "th" tag instead of "td". Default: false
     * @param string[] $columnClasses Optional class attributes to apply to cells (keys are matched against $columns)
     * @param string $rowClass Optional class attribute for the row
     * @return string HTML table row
     */
    public function row(array $columns, $isHeader = false, $columnClasses = array(), $rowClass = null)
    {
        $row = '';
        foreach ($columns as $key => $column) {
            $row .= $this->_htmlElement->__invoke(
                $isHeader ? 'th' : 'td',
                $column,
                isset($columnClasses[$key]) ? array('class' => $columnClasses[$key]) : null
            );
        }
        return $this->_htmlElement->__invoke(
            'tr',
            $row,
            $rowClass ? array('class' => $rowClass) : null
        );
    }
}
