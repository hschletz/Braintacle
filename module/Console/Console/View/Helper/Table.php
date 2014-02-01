<?php
/**
 * Generate HTML table
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

namespace Console\View\Helper;

/**
 * Generate HTML table
 */
class Table extends \Zend\View\Helper\AbstractHelper
{
    /**
     * EscapeHtml view helper
     * @var \Zend\View\Helper\EscapeHtml
     */
    protected $_escapeHtml;

    /**
     * HtmlTag view helper
     * @var \Library\View\Helper\HtmlTag
     */
    protected $_htmlTag;

    /**
     * ConsoleUrl view helper
     * @var \Console\View\Helper\ConsoleUrl
     */
    protected $_consoleUrl;

    /**
     * DateFormat view helper
     * @var \Zend\I18n\View\Helper\DateFormat
     */
    protected $_dateFormat;

    /**
     * Constructor
     *
     * @param \Zend\View\Helper\EscapeHtml $escapeHtml
     * @param \Library\View\Helper\HtmlTag $HtmlTag
     * @param \Console\View\Helper\ConsoleUrl $consoleUrl
     * @param \Zend\I18n\View\Helper\DateFormat $dateFormat
     */
    public function __construct(
        \Zend\View\Helper\EscapeHtml $escapeHtml,
        \Library\View\Helper\HtmlTag $htmlTag,
        \Console\View\Helper\ConsoleUrl $consoleUrl,
        \Zend\I18n\View\Helper\DateFormat $dateFormat)
    {
        $this->_escapeHtml = $escapeHtml;
        $this->_htmlTag = $htmlTag;
        $this->_consoleUrl = $consoleUrl;
        $this->_dateFormat = $dateFormat;
    }

    /**
     * Generate HTML table
     *
     * $headers is an associative array with header labels. Its keys are used to
     * match corresponding fields in the other arguments. For each header, a
     * corresponding field must be set in the table data or in $renderCallbacks.
     *
     * $data is an array of row objects. Row objects are associative arrays or
     * objects implementing the \ArrayAccess interface.
     *
     * By default, cell data is retrieved from $data and escaped automatically.
     * \Zend_Date objects are rendered as short timestamps (yy-mm-dd hh:mm). The
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
     * If the optional $sorting array contains the "order" and "direction"
     * elements (other elements are ignored), headers are generated as
     * hyperlinks with "order" and "direction" parameters set to the
     * corresponding column. The values denote the sorting in effect for the
     * current request - the header will be marked with an arrow indicating the
     * current sorting. The controller action should evaluate these parameters,
     * sort the data and provide the sorting to the view renderer. The
     * \Console\Mvc\Controller\Plugin\GetOrder controller plugin simplifies
     * these tasks.
     *
     * @param array|\Traversable $data
     * @param array $headers
     * @param array $sorting
     * @param array $renderCallbacks
     */
    function __invoke(
        $data,
        array $headers,
        $sorting=array(),
        $renderCallbacks=array()
    )
    {
        $table = "<table class='alternating'>\n";

        // Generate header row
        if (isset($sorting['order']) and isset($sorting['direction'])) {
            $row = array();
            foreach ($headers as $key => $label) {
                $row[] = $this->sortableHeader($label, $key, $sorting['order'], $sorting['direction']);
            }
            $table .= $this->row($row, true);
        } else {
            $table .= $this->row($headers, true);
        }

        // Generate data rows
        $keys = array_keys($headers);
        foreach ($data as $rowData) {
            $row = array();
            foreach ($keys as $key) {
                if (isset($renderCallbacks[$key])) {
                    $row[] = $renderCallbacks[$key]($this->view, $rowData, $key);
                } elseif ($rowData[$key] instanceof \Zend_Date) {
                    $row[] = $this->_escapeHtml->__invoke(
                        $this->_dateFormat->__invoke(
                            (int) $rowData[$key]->get(\Zend_Date::TIMESTAMP),
                            \IntlDateFormatter::SHORT,
                            \IntlDateFormatter::SHORT
                        )
                    );
                } else {
                    $row[] = $this->_escapeHtml->__invoke($rowData[$key]);
                }
            }
            $table .= $this->row($row, false);
        }

        $table .= "</table>\n";
        return $table;
    }

    /**
     * Generate a header hyperlink
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
        return $this->_htmlTag->__invoke(
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
     * @param bool $isHeader Use "th" tag instead of "td".
     * @return string HTML table row
     */
    public function row(array $columns, $isHeader)
    {
        $row = '';
        foreach ($columns as $column) {
            $row .= $this->_htmlTag->__invoke($isHeader ? 'th' : 'td', $column);
        }
        return $this->_htmlTag->__invoke('tr', $row);
    }
}
