<?php

/**
 * Hydrator for software
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

namespace Database\Hydrator;

use Model\AbstractModel;

/**
 * Hydrator for software
 *
 * Picks relevant OS-specific fields and decorates some values.
 */
class Software implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Map for hydrateName()
     *
     * @var string[]
     */
    protected $_hydratorMap = [
        'name' => 'name',
        'version' => 'version',
        'comment' => 'comment',
        'publisher' => 'publisher',
        'install_location' => 'installLocation',
        'is_hotfix' => 'isHotfix',
        'guid' => 'guid',
        'language' => 'language',
        'installation_date' => 'installationDate',
        'architecture' => 'architecture',
        'size' => 'size',
    ];

    /**
     * Map for extractName()
     *
     * @var string[]
     */
    protected $_extractorMap = [
        'name' => 'name',
        'version' => 'version',
        'comment' => 'comment',
        'publisher' => 'publisher',
        'installLocation' => 'install_location',
        'isHotfix' => 'is_hotfix',
        'guid' => 'guid',
        'language' => 'language',
        'installationDate' => 'installation_date',
        'architecture' => 'architecture',
        'size' => 'size',
    ];

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        if ($data['is_windows']) {
            $object->name = $this->hydrateValue('name', $data['name']);
            $object->version = $data['version'];
            $object->comment = $data['comment'];
            $object->publisher = $data['publisher'];
            $object->installLocation = $this->hydrateValue('installLocation', $data['install_location']);
            $object->isHotfix = $this->hydrateValue('isHotfix', $data['is_hotfix']);
            $object->guid = $data['guid'];
            $object->language = $data['language'];
            $object->installationDate = $this->hydrateValue('installationDate', $data['installation_date']);
            $object->architecture = $this->hydrateValue('architecture', $data['architecture']);
        } elseif ($data['is_android']) {
            // No value transformations required
            $object->name = $data['name'];
            $object->version = $data['version'];
            $object->publisher = $data['publisher'];
            $object->installLocation = $data['install_location'];
        } else {
            $object->name = $data['name']; // No sanitization required
            $object->version = $data['version'];
            $object->comment = $data['comment'];
            $object->size = $data['size'];
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        // Some properties may be uninitialized depending on client OS. These
        // properties are coalesced to NULL to avoid errors.
        return [
            'name' => $object->name,
            'version' => $object->version,
            'comment' => $object->comment ?? null,
            'publisher' => $object->publisher ?? null,
            'install_location' => $object->installLocation ?? null,
            'is_hotfix' => $this->extractValue('is_hotfix', $object->isHotfix ?? null),
            'guid' => $object->guid ?? null,
            'language' => $object->language ?? null,
            'installation_date' => $this->extractValue('installation_date', $object->installationDate ?? null),
            'architecture' => $object->architecture ?? null,
            'size' => $object->size ?? null,
        ];
    }

    /**
     * Hydrate name
     *
     * @param string $name
     * @return string
     * @throws \DomainException if $name cannot be hydrated
     */
    public function hydrateName($name)
    {
        if (isset($this->_hydratorMap[$name])) {
            return $this->_hydratorMap[$name];
        } else {
            throw new \DomainException('Cannot hydrate name: ' . $name);
        }
    }

    /**
     * Extract name
     *
     * @param string $name
     * @return string
     * @throws \DomainException if $name cannot be extracted
     */
    public function extractName($name)
    {
        $nameCompat = lcfirst($name); // Legacy uppercase notation. Remove when no longer required.
        if (isset($this->_extractorMap[$nameCompat])) {
            return $this->_extractorMap[$nameCompat];
        } else {
            throw new \DomainException('Cannot extract name: ' . $name);
        }
    }

    /**
     * Hydrate value
     *
     * @param string $name
     * @param string $value
     * @return mixed
     */
    public function hydrateValue($name, $value)
    {
        switch ($name) {
            case 'installLocation':
                if ($value == 'N/A') {
                    // One-way removal of pseudo-values
                    $value = null;
                } elseif ($value !== null) {
                    // One-way transformation of forward slashes to more common backslashes
                    $value = str_replace('/', '\\', $value);
                }
                break;
            case 'isHotfix':
                // 0: Windows hotfix, 1: regular software
                $value = !(bool) $value;
                break;
            case 'installationDate':
                $value = ($value ? new \DateTime($value) : null);
                break;
            case 'architecture':
                // One-way removal of pseudo-values
                if ($value == '0') {
                    $value = null;
                }
                break;
        }
        return $value;
    }

    /**
     * Extract value
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function extractValue($name, $value)
    {
        $value = match ($name) {
            'is_hotfix' => $value === null ? null : (int) !$value,
            'installation_date' => $value ? $value->format('Y-m-d') : null,
            default => $value,
        };

        return $value;
    }
}
