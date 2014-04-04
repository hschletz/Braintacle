<?php
/**
 * Submit button with improved button text handling
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

namespace Library\Form\Element;

/**
 * Submit button with improved button text handling
 *
 * HTML submit buttons differ from other form elements in direct support for a
 * text label (via "value" attribute) instead of having to provide a label
 * outside the element markup. \Zend\Form\Element strictly separates the "value"
 * property (mapping to the "value" attribute) from the "label" property
 * (referring to the external label) regardless of element type.
 *
 * This is a problem when extracting translatable strings via xgettext:
 * setLabel() is typically undesirable for submit buttons, while setValue() is
 * not suitable for automatic string extraction because its argument is
 * typically not translatable for other element types.
 *
 * This class extends \Zend\Form\Element\Submit with a new setText() method
 * which proxies to setValue() and can be made recognizable by xgettext.
 */
class Submit extends \Zend\Form\Element\Submit
{
    /**
     * Proxy to setValue()
     *
     * @param string $text Button text
     */
    public function setText($text)
    {
        return $this->setValue($text);
    }
}
