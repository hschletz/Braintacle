<?php
/**
 * Hydrator for Software item
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol\Hydrator;

/**
 * Hydrator for Software item
 */
class Software extends \Database\Hydrator\Software
{
    use DatabaseExtensionTrait;

    /** {@inheritdoc} */
    public function extractValue($name, $value)
    {
        if (strtoupper($name) == 'INSTALLDATE') {
            $value = ($value ? $value->format('Y/m/d') : null);
        } else {
            $value = parent::extractValue(strtolower($name), $value);
        }
        return $value;
    }
}
