<?php
/**
 * Render a HTML table from a DB statement object
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package ViewHelpers
 * @filesource
 */
/**
 * @package ViewHelpers
 */
class Zend_View_Helper_Table extends Zend_View_Helper_Abstract
{

    /**
     * Render a HTML table from a DB statement object.
     *
     * $columns contains a list of properties to be displayed as a column. If
     * this is empty, all properties from $headers will be used. This allows a
     * subset of columns to be displayed and still pass a full list for $headers,
     * $formats and $renderCallbacks to be passed, thus simplifying calling code.
     *
     * $headers must contain translated strings.
     *
     * $formats contains the datatype ('integer', 'text', 'timestamp' etc.) for
     * each property. This affects how the value is rendered (i.e. aligned and
     * formatted). If no datatype is specified for a particular property, it is
     * determined automatically from the model class. If the default style is
     * not sufficient, any value different from the standard datatypes will be
     * interpreted as the name of a callback function. This function will be
     * called with 3 parameters:
     * 1. A model object with row data, or NULL if the header is to be formatted
     * 2. The name of the property.
     * 3. The view object
     * Its return value will be used as a 'class' attribute for the cell.
     *
     * If the default rendering for data cells is not sufficient, a callback
     * function can be provided in the $renderCallbacks array. The function
     * will be called with 3 parameters:
     * 1. The view object
     * 2. A model object with the data row
     * 3. The name of the property
     * Its return value will be used as cell content. It must be properly escaped.
     *
     * Another callback function can be given in the optional $rowFormatCallback
     * parameter. If this is set, it will be called with 2 parameters:
     * 1. A model object with row data, or NULL if the header is to be formatted
     * 2. The view object
     *
     * The number of rows is optionally returned in the variable which is passed
     * by reference in the $rowCount parameter. To make this work, the helper
     * must be called this way:
     *
     * $table = $this->getHelper('table')->table(...)
     *
     * The shorthand notation $this->table(...) will not work.
     *
     * @param Zend_Db_Statement $statement Data to be rendered
     * @param array $columns List of properties to be displayed. If empty, all properties from $headers are displayed.
     * @param array $headers Associative array with property as key and translated header as value.
     * @param array $formats property => (datatype|callback). Missing values will be determined automatically.
     * @param string $modelClass Name of the class to hold the fetched data. Must be derived from Model_Abstract.
     * @param string $rowFormatCallback Optional name of callback function to return class attribute for a given row
     * @param array $renderCallbacks Optional array with property as key and name of callback function for rendering
     * @param int &$rowCount Optional reference to a variable that will hold the number of rows
     * @return string HTML table output
     */
    function table(
        Zend_Db_Statement $statement,
        $columns,
        $headers,
        $formats,
        $modelClass,
        $rowFormatCallback = null,
        $renderCallbacks=array(),
        &$rowCount=null
    )
    {
        if (empty($columns)) {
            $columns = array_keys($headers);
        }

        $object = new $modelClass;
        // Determine missing formats
        foreach ($columns as $column) {
            if (!isset($formats[$column])) {
                $formats[$column] = $object->getPropertyType($column);
            }
        }

        $table = "<table>\n";

        // Generate header row
        if (property_exists($this->view, 'order')) {
            foreach ($columns as $column) {
                $row[] = $this->view->sortableHeader($headers[$column], $column);
            }
        } else {
            $row = array_values($headers);
        }
        $table .= $this->view->tableRow(
            $row,
            $this->_buildFormats($columns, $formats),
            $rowFormatCallback ? $rowFormatCallback(null, $this->view) : null,
            'th'
        );

        // Generate data row
        $rowCount = 0;
        while ($object = $statement->fetchObject($modelClass)) {
            $rowCount++;
            $row = array();
            foreach ($columns as $column) {
                if (array_key_exists($column, $renderCallbacks)) {
                    $row[] = $renderCallbacks[$column]($this->view, $object, $column);
                } else {
                    $value = $object->getProperty($column);
                    if (!is_null($value)) {
                        // use localized output format
                        switch ($formats[$column]) {
                            case 'integer':
                            case 'decimal':
                            case 'float':
                                $value = Zend_Locale_Format::toNumber($value);
                                break;
                            case 'date':
                                $value = $this->view->date($value, Zend_Date::DATE_MEDIUM);
                                break;
                            case 'time':
                                $value = $this->view->date($value, Zend_Date::TIME_MEDIUM);
                                break;
                            case 'timestamp':
                                $value = $this->view->date($value, Zend_Date::DATETIME_SHORT);
                                break;
                        }
                    }
                    $row[] = $this->view->escape($value);
                }
            }
            $table .= $this->view->tableRow(
                $row,
                $this->_buildFormats($columns, $formats, $object),
                $rowFormatCallback ? $rowFormatCallback($object, $this->view) : null
            );
        }

        $table .= "</table>\n";
        return $table;
    }

    /**
     * Determine <class> attributes for each cell in a row
     * @param array $columns Property names of all columns
     * @param array $formats Associative array with format descriptions
     * @param mixed Object with row data (default: NULL, for header row)
     * @return array List of <class> attributes for each column
     */
    protected function _buildFormats($columns, $formats, $row=null)
    {
        foreach ($columns as $column) {
            $format = $formats[$column];
            switch ($format) {
                case 'integer':
                case 'decimal':
                case 'float':
                    $classes[] = 'textright';
                    break;
                case 'text':
                case 'boolean':
                case 'clob':
                case 'date':
                case 'time':
                case 'timestamp':
                case 'enum':
                    $classes[] = 'textcenter';
                    break;
                case 'blob':
                    throw new UnexpectedValueException(
                        'Default format for blob type is not defined; a callback is required to render blob columns.'
                    );
                default:
                    $classes[] = $format($row, $column, $this->view);
            }
        }
        return $classes;
    }
}
