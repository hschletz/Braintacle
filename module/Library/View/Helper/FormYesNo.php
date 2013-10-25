<?php
/**
 * Render a Yes/No form with caption
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

namespace Library\View\Helper;

/**
 * Render a Yes/No form with caption
 *
 * The output is a simple form with 2 Buttons, labeled Yes/No or their
 * translations. Form action is empty (current action), method is 'POST' and the
 * buttons are named 'yes' and 'no'. The caption is rendered as a paragraph
 * above the form.
 */
class FormYesNo extends \Zend\View\Helper\AbstractHelper
{
    /**
     * Render Form
     *
     * @param string $caption Any valid HTML code. Calling code must escape content if necessary.
     * @return string Form code
     */
    public function __invoke($caption)
    {
        return sprintf(
            "<p>%s</p>\n" .
            "<form action='' method='POST'>\n" .
            "<p><input type='submit' name='yes' value='%s'>&nbsp;\n" .
            "<input type='submit' name='no' value='%s'></p>\n" .
            "</form>\n",
            $caption,
            $this->view->translate('Yes'),
            $this->view->translate('No')
        );
    }
}
