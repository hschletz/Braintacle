<?php

/**
 * Hydrator for clients (BIOS section)
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

namespace Protocol\Hydrator;

use Model\AbstractModel;

/**
 * Hydrator for clients (BIOS section)
 *
 * Unlike with other hydrators, objects are not reset by hydrate(), i.e. data is
 * merged with previous content. Unknown names are ignored.
 */
class ClientsBios implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Map for hydrateName()
     *
     * @var string[]
     */
    protected $_hydratorMap = array(
        'ASSETTAG' => 'assetTag',
        'BDATE' => 'biosDate',
        'BMANUFACTURER' => 'biosManufacturer',
        'BVERSION' => 'biosVersion',
        'SMANUFACTURER' => 'manufacturer',
        'SMODEL' => 'productName',
        'SSN' => 'serial',
        'TYPE' => 'type',
    );

    /**
     * Map for extractName()
     *
     * @var string[]
     */
    protected $_extractorMap = [
        'assetTag' => 'ASSETTAG',
        'biosDate' => 'BDATE',
        'biosManufacturer' => 'BMANUFACTURER',
        'biosVersion' => 'BVERSION',
        'manufacturer' => 'SMANUFACTURER',
        'productName' => 'SMODEL',
        'serial' => 'SSN',
        'type' => 'TYPE',
    ];

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        foreach ($data as $name => $value) {
            $name = $this->hydrateName($name);
            if ($name) {
                $object->$name = $value;
            }
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = array();
        foreach (get_object_vars($object) as $name => $value) {
            $name = $this->extractName($name);
            if ($name) {
                $data[$name] = $value;
            }
        }
        return $data;
    }

    /**
     * Hydrate name
     *
     * @param string $name
     * @return string|null
     */
    public function hydrateName($name)
    {
        return @$this->_hydratorMap[$name];
    }

    /**
     * Extract name
     *
     * @param string $name
     * @return string|null
     */
    public function extractName($name)
    {
        return @$this->_extractorMap[$name];
    }
}
