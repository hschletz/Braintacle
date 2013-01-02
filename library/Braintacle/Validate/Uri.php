<?php
/**
 * URI validation class
 *
 * $Id$
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
 *
 * @package Library
 */
/** Validator class that checks whether a given string is a valid URI.
  * The scheme is passed to the constructor and must not be part of the
  * checked string.
 * @package Library
  */
class Braintacle_Validate_Uri extends Zend_Validate_Abstract
{
    const URI = 'uri';

    /**
     * Additional variables available for validation failure messages
     * @var array
     */
    protected $_messageVariables = array(
        'scheme' => 'scheme',
    );

    /**
     * Validation failure message template definitions
     * @var array
     */
    protected $_messageTemplates = array(
        self::URI => "'%value%' is not a valid URI (without '%scheme%://')"
    );

    /**
     * URI scheme, ex. 'http'
     * @var string
     */
    public $scheme;

    /**
     * Returns true if $value is a valid URI (as checked by Zend_Uri).
     * The scheme must not be prepended as it will be prepended automatically!
     * @param string $value String to be validated
     * @return bool whether this is a valid URI.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        if (Zend_Uri::check("$this->scheme://$value")) {
            return true;
        }

        $this->_error(self::URI);
        return false;
    }

    /**
     * Constructor.
     * @param string $scheme URI scheme (without ''://'') that must be
     * recognized by Zend_Uri.
     */
    public function __construct($scheme)
    {
        $this->scheme = $scheme;
    }
}
