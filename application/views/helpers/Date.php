<?php
/**
 * Render date value using Zend_Date
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
 */
/**
 * Render date value using Zend_Date
 * @package ViewHelpers
 */
class Zend_View_Helper_Date extends Zend_View_Helper_Abstract
{

    /**
     * Render date value using Zend_Date
     *
     * For input and output formats, all constants listed in
     * {@link http://framework.zend.com/manual/en/zend.date.constants.html}
     * can be used. If the value is a Zend_Date object, the input format will be
     * ignored.
     *
     * @param mixed $value Value to be rendered
     * @param mixed $outputFormat Format of return value, default: Zend_Date::DATETIME_SHORT
     * @param mixed $inputFormat Format of $value, default: Zend_Date::ISO_8601
     * @return string Formatted date value, unescaped
     */
    function date ($value, $outputFormat=Zend_Date::DATETIME_SHORT, $inputFormat=Zend_Date::ISO_8601)
    {
        if (!($value instanceof Zend_Date)) {
            $value = new Zend_Date($value, $inputFormat);
        }
        return $value->get($outputFormat);
    }

}
