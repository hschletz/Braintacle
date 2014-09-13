<?php
/**
 * Validate string length
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

namespace Library\Validator;

/**
 * Validate string length
 *
 * This validator extends ZF's StringLength validator to accept NULL input and
 * treat it as an empty string (i.e. the 'min' option is still effective). This
 * is useful to work around a bug in the InputFilter where NULL values may get
 * passed to the validator chain even when empty values are explicitly allowed.
 */
class StringLength extends \Zend\Validator\StringLength
{
    /** {@inheritdoc} */
    public function isValid($value)
    {
        if ($value === null) {
            $value = '';
        }
        return parent::isValid($value);
    }
}
