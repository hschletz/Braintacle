<?php
/**
 * Render a HTML table row
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
class Zend_View_Helper_TableRow extends Zend_View_Helper_Abstract
{

    /**
     * current state of row highlighting
     * @var bool
     */
    protected $_highlight;

    /**
     * Render a HTML table row.
     * @param array $columns Content of each column
     * @param array $classes classes for each column (null for no class)
     * @param array $rowClass class for row element
     * @param string $type Type of row (td or th)
     * @return string Table row
     */
    function tableRow($columns, $classes, $rowClass=null, $type='td')
    {
        // Since only 1 instance of this class is maintained, _highlight needs
        // to be reset for new tables, i.e. for <th> rows. This ensures correct
        // rendering of multiple tables on the same page.
        if ($type == 'th') {
            $this->_highlight = false;
        }

        $row = '';

        // render columns
        foreach ($columns as $key => $content) {
            $row .= $this->view->htmlTag(
                $type, '    ' . $content,
                $classes[$key] ? array('class' => $classes[$key]) : null
            );
        }

        // highlight every second row
        if ($this->_highlight) {
            $rowClass .= ' bgcolor';
        }
        $this->_highlight = ! $this->_highlight; // invert for next row

        return $this->view->htmlTag(
            'tr',
            $row,
            $rowClass ? array('class' => trim($rowClass)) : null
        );
    }

}
