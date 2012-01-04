<?php
/**
 * Render a clickable HTML table header
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
class Zend_View_Helper_SortableHeader extends Zend_View_Helper_Abstract
{

    /**
     * Render a clickable HTML table header.
     * @param string $label Text to put in the header
     * @param string $order Value to be set as order= argument
     * @return string <a> tag
     */
    function sortableHeader ($label, $order)
    {
        if ($order == $this->view->order) {
            // add arrow indicator to currently sorted column and
            // invert direction for the hyperlink.
            if ($this->view->direction == 'asc') {
                $direction = 'desc';
                $arrow = '&uarr;';
            } else {
                $direction = 'asc';
                $arrow = '&darr;';
            }
        } else {
            // use ascending ordering for every other hyperlink.
            $direction = 'asc';
            $arrow = '';
        }

        return $this->view->htmlTag(
            'a',
            $label . $arrow,
            array(
                'href' => $this->view->url(
                    array('order' => $order, 'direction' => $direction)
                )
            ),
            true
        );
    }

}
