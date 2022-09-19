<?php

/**
 * Validate string as an MS product key
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

namespace Library\Validator;

/**
 * Validate string as an MS product key
 *
 * A valid product key has 5 groups of 5 upper case characters or digits,
 * separated by dashes, i.e. AAAAA-AAAAA-AAAAA-AAAAA-AAAAA.
 */
class ProductKey extends \Laminas\Validator\AbstractValidator
{
    /**
     * Key for message template
     */
    const PRODUCT_KEY = 'productKey';

    /**
     * Validation failure message template definitions
     * @var string[]
     */
    protected $messageTemplates = array(
        self::PRODUCT_KEY => "'%value%' is not a valid product key",
    );

    /**
     * Returns TRUE if $value is a valid product key
     *
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);
        if (preg_match('/^[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $value)) {
            return true;
        } else {
            $this->error(self::PRODUCT_KEY);
            return false;
        }
    }
}
