<?php

/**
 * Naming strategy using a map
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

namespace Database\Hydrator\NamingStrategy;

use Laminas\Hydrator\NamingStrategy\MapNamingStrategy as WrappedMapNamingStrategy;

/**
 * Naming strategy using a map
 *
 * Wrapper for \Laminas\Hydrator\NamingStrategy\MapNamingStrategy throwing an
 * exception on undefined values. hydrate() accepts both extracted and hydrated
 * names.
 */
class MapNamingStrategy implements \Laminas\Hydrator\NamingStrategy\NamingStrategyInterface
{
    /**
     * Wrapped MapNamingStrategy
     * @var WrappedMapNamingStrategy
     */
    protected $mapNamingStrategy;

    /**
     * Hydration map
     * @var array
     */
    protected $hydrationMap;

    /**
     * Extraction map
     * @var array
     */
    protected $extractionMap;

    /**
     * Constructor.
     *
     * @param array $hydrationMap Hydradion map
     * @param null|array $extractionMap Optional extraction map, if not the reverse of hydration map.
     */
    public function __construct(array $hydrationMap, ?array $extractionMap = null)
    {
        if ($extractionMap === null) {
            $extractionMap = array_flip($hydrationMap);
        }
        $this->mapNamingStrategy = WrappedMapNamingStrategy::createFromAsymmetricMap($extractionMap, $hydrationMap);
        $this->hydrationMap = $hydrationMap;
        $this->extractionMap = $extractionMap;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException if $name is not defined in the map
     */
    public function hydrate(string $name, ?array $data = null): string
    {
        $hydratedName = $this->mapNamingStrategy->hydrate($name, $data);
        if (
            $hydratedName == $name and
            !isset($this->extractionMap[$name]) and
            !in_array($name, $this->hydrationMap)
        ) {
            throw new \InvalidArgumentException('Unknown column name: ' . $name);
        }
        return $hydratedName;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException if $name is not defined in the map
     */
    public function extract(string $name, ?object $object = null): string
    {
        $extractedName = $this->mapNamingStrategy->extract($name, $object);
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
    public function getHydrationMap(): array
    {
        return $this->hydrationMap;
    }

    /**
     * Get map for extracting names
     *
     * @return string[]
     */
    public function getExtractionMap(): array
    {
        return $this->extractionMap;
    }
}
