<?php

/**
 * Decode compressed inventory data
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

namespace Protocol\Filter;

/**
 * Decode compressed inventory data
 *
 * This filter decodes zlib compressed inventory data which some agents generate
 * instead of uncompressed XML.
 */
class InventoryDecode extends \Laminas\Filter\AbstractFilter
{
    /**
     * {@inheritdoc}
     * @throws \RuntimeException if zlib initialization error occurs
     * @throws \InvalidArgumentException if an error occurs while decoding (most likely invalid input)
     */
    public function filter($value)
    {
        $context = inflate_init(ZLIB_ENCODING_DEFLATE);
        if (!$context) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Could not create inflate context');
            // @codeCoverageIgnoreEnd
        }
        $output = @inflate_add($context, $value);
        if ($output === false) {
            throw new \InvalidArgumentException('Input does not appear to be a zlib stream');
        }
        return $output;
    }
}
