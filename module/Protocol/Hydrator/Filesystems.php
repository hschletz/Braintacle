<?php
/**
 * Hydrator for Filesystems item
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 * Hydrator for Filesystems item
 */
class Filesystems extends \Database\Hydrator\Filesystems
{
    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        return parent::hydrate(array_change_key_case($data, CASE_LOWER), $object);
    }

    /** {@inheritdoc} */
    public function extract($object)
    {
        return array_change_key_case(parent::extract($object), CASE_UPPER);
    }

    /** {@inheritdoc} */
    public function hydrateName($name)
    {
        return parent::hydrateName(strtolower($name));
    }

    /** {@inheritdoc} */
    public function extractName($name)
    {
        return strtoupper(parent::extractName($name));
    }

    /** {@inheritdoc} */
    public function hydrateValue($name, $value)
    {
        if ($name == 'CreationDate') {
            $value = ($value ? new \Zend_Date($value, 'yyyy/M/d HH:mm:ss') : null);
        } else {
            $value = parent::hydrateValue($name, $value);
        }
        return $value;
    }

    /** {@inheritdoc} */
    public function extractValue($name, $value)
    {
        if (strtoupper($name) == 'CREATEDATE') {
            $value = ($value ? $value->get('yyyy/M/d HH:mm:ss') : null);
        } else {
            $value = parent::extractValue(strtolower($name), $value);
        }
        return $value;
    }
}
