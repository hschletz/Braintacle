<?php
/**
 * Naming strategy using a map
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

namespace Database\Hydrator\NamingStrategy;

/**
 * Naming strategy using a map
 *
 * Extends MapNamingStrategy to throw an exception on undefined values.
 * Hydrated/extracted names must differ from the original name.
 */
class MapNamingStrategy extends \Zend\Hydrator\NamingStrategy\MapNamingStrategy
{
    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException if $name is not defined in the map
     */
    public function hydrate($name)
    {
        $hydratedName = parent::hydrate($name);
        if ($hydratedName == $name) {
            throw new \InvalidArgumentException('Unknown column name: ' . $name);
        }
        return $hydratedName;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException if $name is not defined in the map
     */
    public function extract($name)
    {
        $extractedName = parent::extract($name);
        if ($extractedName == $name) {
            throw new \InvalidArgumentException('Unknown property name: ' . $name);
        }
        return $extractedName;
    }

    /**
     * Get map for hydrating names
     *
     * @return string[]
     */
    public function getHydratorMap()
    {
        return $this->mapping;
    }

    /**
     * Get map for extracting names
     *
     * @return string[]
     */
    public function getExtractorMap()
    {
        return $this->reverse;
    }
}
