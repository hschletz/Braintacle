<?php

/**
 * Select element with simplified options (no value attributes)
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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
 * Select element with simplified options (no value attributes)
 *
 * This Select element ignores the keys of the valueOptions array. It is
 * accompanied by a view helper (FormSelectSimple) which renders the options
 * without the "value" attribute. The helper is registered with the
 * FormElement helper to render the element correctly.
 */
class SelectSimple extends \Laminas\Form\Element\Select
{
    protected function getValueOptionsValues(): array
    {
        // Override parent method - return values for InArray validator
        return $this->getValueOptions();
    }

    /** {@inheritdoc} */
    protected function getOptionValue($key, $optionSpec)
    {
        // Parent implementation of getValueOptionsValues() calls this -
        // should not happen with reimplemented method.
        throw new \LogicException(__METHOD__ . '() should never be called');
    }
}
