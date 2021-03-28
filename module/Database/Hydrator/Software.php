<?php

/**
 * Hydrator for software
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

/**
 * Hydrator for software
 *
 * Picks relevant OS-specific fields and decorates some values.
 */
class Software implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Filter for hydration of "Name"
     *
     * @var \Library\Filter\FixEncodingErrors
     */
    protected $_nameFilter;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_nameFilter = new \Library\Filter\FixEncodingErrors();
    }

    /**
     * Map for hydrateName()
     *
     * @var string[]
     */
    protected $_hydratorMap = [
        'name' => 'Name',
        'version' => 'Version',
        'comment' => 'Comment',
        'publisher' => 'Publisher',
        'install_location' => 'InstallLocation',
        'is_hotfix' => 'IsHotfix',
        'guid' => 'Guid',
        'language' => 'Language',
        'installation_date' => 'InstallationDate',
        'architecture' => 'Architecture',
        'size' => 'Size',
    ];

    /**
     * Map for extractName()
     *
     * @var string[]
     */
    protected $_extractorMap = [
        'Name' => 'name',
        'Version' => 'version',
        'Comment' => 'comment',
        'Publisher' => 'publisher',
        'InstallLocation' => 'install_location',
        'IsHotfix' => 'is_hotfix',
        'Guid' => 'guid',
        'Language' => 'language',
        'InstallationDate' => 'installation_date',
        'Architecture' => 'architecture',
        'Size' => 'size',
    ];

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        $object->exchangeArray(array());
        if ($data['is_windows']) {
            $object['Name'] = $this->hydrateValue('Name', $data['name']);
            $object['Version'] = $data['version'];
            $object['Comment'] = $data['comment'];
            $object['Publisher'] = $data['publisher'];
            $object['InstallLocation'] = $this->hydrateValue('InstallLocation', $data['install_location']);
            $object['IsHotfix'] = $this->hydrateValue('IsHotfix', $data['is_hotfix']);
            $object['Guid'] = $data['guid'];
            $object['Language'] = $data['language'];
            $object['InstallationDate'] = $this->hydrateValue('InstallationDate', $data['installation_date']);
            $object['Architecture'] = $this->hydrateValue('Architecture', $data['architecture']);
        } elseif ($data['is_android']) {
            // No value transformations required
            $object['Name'] = $data['name'];
            $object['Version'] = $data['version'];
            $object['Publisher'] = $data['publisher'];
            $object['InstallLocation'] = $data['install_location'];
        } else {
            $object['Name'] = $data['name']; // No sanitization required
            $object['Version'] = $data['version'];
            $object['Comment'] = $data['comment'];
            $object['Size'] = $data['size'];
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        if (property_exists($object, 'IsHotfix')) {
            // Windows
            $data = array(
                'name' => $object->Name,
                'version' => $object->Version,
                'comment' => $object->Comment,
                'publisher' => $object->Publisher,
                'install_location' => $object->InstallLocation,
                'is_hotfix' => $this->extractValue('is_hotfix', $object->IsHotfix),
                'guid' => $object->Guid,
                'language' => $object->Language,
                'installation_date' => $this->extractValue('installation_date', $object->InstallationDate),
                'architecture' => $object->Architecture,
                'size' => null,
            );
        } elseif (property_exists($object, 'Size')) {
            // UNIX
            $data = array(
                'name' => $object->Name,
                'version' => $object->Version,
                'comment' => $object->Comment,
                'publisher' => null,
                'install_location' => null,
                'is_hotfix' => null,
                'guid' => null,
                'language' => null,
                'installation_date' => null,
                'architecture' => null,
                'size' => $object->Size,
            );
        } else {
            //Android
            $data = array(
                'name' => $object->Name,
                'version' => $object->Version,
                'comment' => null,
                'publisher' => $object->Publisher,
                'install_location' => $object->InstallLocation,
                'is_hotfix' => null,
                'guid' => null,
                'language' => null,
                'installation_date' => null,
                'architecture' => null,
                'size' => null,
            );
        }
        return $data;
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
        if (isset($this->_extractorMap[$name])) {
            return $this->_extractorMap[$name];
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
            case 'Name':
                // One-way correction of characters improperly encoded by old agents
                $value = $this->_nameFilter->filter($value);
                break;
            case 'InstallLocation':
                if ($value == 'N/A') {
                    // One-way removal of pseudo-values
                    $value = null;
                } else {
                    // One-way transformation of forward slashes to more common backslashes
                    $value = str_replace('/', '\\', $value);
                }
                break;
            case 'IsHotfix':
                // 0: Windows hotfix, 1: regular software
                $value = !(bool) $value;
                break;
            case 'InstallationDate':
                $value = ($value ? new \DateTime($value) : null);
                break;
            case 'Architecture':
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
     * @param string $value
     * @return mixed
     */
    public function extractValue($name, $value)
    {
        switch ($name) {
            case 'is_hotfix':
                $value = (int) !$value;
                break;
            case 'installation_date':
                $value = ($value ? $value->format('Y-m-d') : null);
                break;
        }
        return $value;
    }
}
