<?php

/**
 * Submit button with improved button text handling
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

namespace Library\Form\Element;

/**
 * Submit button with improved button text handling
 *
 * HTML submit buttons differ from other form elements in direct support for a
 * text label (via "value" attribute) instead of having to provide a label
 * outside the element markup. Unlike other elements, the "value" attribute is
 * not really part of the data layer but of the view layer. This inconsistency
 * causes several problems:
 *
 * 1. The element and its "value" attribute's content is part of the submitted
 *    form data and treated like any other form element. This may overwrite
 *    existing button labels, and the view helper will try to translate an
 *    already translated string.
 *
 * 2. \Laminas\Form\Element strictly separates the "value" property (mapping to
 *    the "value" attribute) from the "label" property (referring to the
 *    external label) regardless of element type. This is a problem when
 *    extracting translatable strings via xgettext: setLabel() is typically
 *    undesirable for submit buttons, while setValue() is not suitable for
 *    automatic string extraction because its argument is typically not
 *    translatable for other element types.
 *
 * This class reimplements setValue() with a no-op, ignoring submitted form
 * data. The button label must be set via the reimplemented setLabel().
 */
class Submit extends \Laminas\Form\Element\Submit
{
    /** {@inheritdoc} */
    public function setValue($value)
    {
        return $this;
    }

    /** {@inheritdoc} */
    public function setLabel($label)
    {
        return parent::setValue($label);
    }
}
