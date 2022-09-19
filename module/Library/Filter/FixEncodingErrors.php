<?php

/**
 * Fix effects of incorrect charset conversion by old agents
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

namespace Library\Filter;

/**
 * Fix effects of incorrect charset conversion by old agents
 *
 * Old agents have a bug where Windows-1252 encoded strings are incorrectly
 * interpreted as ISO 8859-1 when converting to UTF-8, causing characters
 * 0x80-0x9F to be interpreted as unprintable control characters and converted
 * to the wrong UTF-8 characters. The resulting strings are still valid UTF-8,
 * so they get stored in the database without errors.
 *
 * This filter takes an UTF-8 string and replaces the incorrect characters with
 * their original counterparts. It's safe to apply this filter to arbitrary
 * UTF-8 strings, even if it's not affected by the bug.
 */
class FixEncodingErrors extends \Laminas\Filter\AbstractFilter
{
    /**
     * UTF-8 sequences of characters to replace
     * @var string[]
     */
    protected static $_badChars = array(
        "\xC2\x80", // Euro sign
        "\xC2\x82", // Single Low-9 Quotation Mark
        "\xC2\x83", // Latin Small Letter F With Hook
        "\xC2\x84", // Double Low-9 Quotation Mark
        "\xC2\x85", // Horizontal Ellipsis
        "\xC2\x86", // Dagger
        "\xC2\x87", // Double Dagger
        "\xC2\x88", // Modifier Letter Circumflex Accent
        "\xC2\x89", // Per Mille Sign
        "\xC2\x8A", // Latin Capital Letter S With Caron
        "\xC2\x8B", // Single Left-Pointing Angle Quotation Mark
        "\xC2\x8C", // Latin Capital Ligature OE
        "\xC2\x8E", // Latin Capital Letter Z With Caron
        "\xC2\x91", // Left Single Quotation Mark
        "\xC2\x92", // Right Single Quotation Mark
        "\xC2\x93", // Left Double Quotation Mark
        "\xC2\x94", // Right Double Quotation Mark
        "\xC2\x95", // Bullet
        "\xC2\x96", // En Dash
        "\xC2\x97", // Em Dash
        "\xC2\x98", // Small Tilde
        "\xC2\x99", // Trade Mark Sign
        "\xC2\x9A", // Latin Small Letter S With Caron
        "\xC2\x9B", // Single Right-Pointing Angle Quotation Mark
        "\xC2\x9C", // Latin Small Ligature OE
        "\xC2\x9E", // Latin Small Letter Z With Caron
        "\xC2\x9F", // Latin Capital Letter Y With Diaeresis
    );

    /**
     * UTF-8 sequences of replacement characters
     * @var string[]
     */
    protected static $_goodChars = array(
        "\xE2\x82\xAC", // Euro sign
        "\xE2\x80\x9A", // Single Low-9 Quotation Mark
        "\xC6\x92",     // Latin Small Letter F With Hook
        "\xE2\x80\x9E", // Double Low-9 Quotation Mark
        "\xE2\x80\xA6", // Horizontal Ellipsis
        "\xE2\x80\xA0", // Dagger
        "\xE2\x80\xA1", // Double Dagger
        "\xCB\x86",     // Modifier Letter Circumflex Accent
        "\xE2\x80\xB0", // Per Mille Sign
        "\xC5\xA0",     // Latin Capital Letter S With Caron
        "\xE2\x80\xB9", // Single Left-Pointing Angle Quotation Mark
        "\xC5\x92",     // Latin Capital Ligature OE
        "\xC5\xBD",     // Latin Capital Letter Z With Caron
        "\xE2\x80\x98", // Left Single Quotation Mark
        "\xE2\x80\x99", // Right Single Quotation Mark
        "\xE2\x80\x9C", // Left Double Quotation Mark
        "\xE2\x80\x9D", // Right Double Quotation Mark
        "\xE2\x80\xA2", // Bullet
        "\xE2\x80\x93", // En Dash
        "\xE2\x80\x94", // Em Dash
        "\xCB\x9C",     // Small Tilde
        "\xE2\x84\xA2", // Trade Mark Sign
        "\xC5\xA1",     // Latin Small Letter S With Caron
        "\xE2\x80\xBA", // Single Right-Pointing Angle Quotation Mark
        "\xC5\x93",     // Latin Small Ligature OE
        "\xC5\xBE",     // Latin Small Letter Z With Caron
        "\xC5\xB8",     // Latin Capital Letter Y With Diaeresis
    );

    /** {@inheritdoc} */
    public function filter($value)
    {
        return str_replace(static::$_badChars, static::$_goodChars, $value);
    }
}
