<?php
/**
 * Hydrator for software
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
class Software implements \Zend\Hydrator\HydratorInterface
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
        $this->_nameFilter = new \Library\Filter\FixEncodingErrors;
    }

    /**
     * Map for hydrateName()
     *
     * @var string[]
     */
    protected $_hydratorMap = array(
        'name' => 'Name',
        'version' => 'Version',
        'comments' => 'Comment',
        'publisher' => 'Publisher',
        'folder' => 'InstallLocation',
        'source' => 'IsHotfix',
        'guid' => 'Guid',
        'language' => 'Language',
        'installdate' => 'InstallationDate',
        'bitswidth' => 'Architecture',
        'filesize' => 'Size',
    );

    /**
     * Map for extractName()
     *
     * @var string[]
     */
    protected $_extractorMap = array(
        'Name' => 'name',
        'Version' => 'version',
        'Comment' => 'comments',
        'Publisher' => 'publisher',
        'InstallLocation' => 'folder',
        'IsHotfix' => 'source',
        'Guid' => 'guid',
        'Language' => 'language',
        'InstallationDate' => 'installdate',
        'Architecture' => 'bitswidth',
        'Size' => 'filesize',
    );

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        $object->exchangeArray(array());
        if ($data['is_windows']) {
            $object['Name'] = $this->hydrateValue('Name', $data['name']);
            $object['Version'] = $data['version'];
            $object['Comment'] = $data['comments'];
            $object['Publisher'] = $data['publisher'];
            $object['InstallLocation'] = $this->hydrateValue('InstallLocation', $data['folder']);
            $object['IsHotfix'] = $this->hydrateValue('IsHotfix', $data['source']);
            $object['Guid'] = $data['guid'];
            $object['Language'] = $data['language'];
            $object['InstallationDate'] = $this->hydrateValue('InstallationDate', $data['installdate']);
            $object['Architecture'] = $this->hydrateValue('Architecture', $data['bitswidth']);
        } elseif ($data['is_android']) {
            // No value transformations required
            $object['Name'] = $data['name'];
            $object['Version'] = $data['version'];
            $object['Publisher'] = $data['publisher'];
            $object['InstallLocation'] = $data['folder'];
        } else {
            $object['Name'] = $data['name']; // No sanitization required
            $object['Version'] = $data['version'];
            $object['Comment'] = $data['comments'];
            $object['Size'] = $data['filesize'];
        }
        return $object;
    }

    /** {@inheritdoc} */
    public function extract($object)
    {
        if (array_key_exists('IsHotfix', $object)) {
            // Windows
            $data = array(
                'name' => $object['Name'],
                'version' => $object['Version'],
                'comments' => $object['Comment'],
                'publisher' => $object['Publisher'],
                'folder' => $object['InstallLocation'],
                'source' => $this->extractValue('source', $object['IsHotfix']),
                'guid' => $object['Guid'],
                'language' => $object['Language'],
                'installdate' => $this->extractValue('installdate', $object['InstallationDate']),
                'bitswidth' => $object['Architecture'],
                'filesize' => null,
            );
        } elseif (array_key_exists('Size', $object)) {
            // UNIX
            $data = array(
                'name' => $object['Name'],
                'version' => $object['Version'],
                'comments' => $object['Comment'],
                'publisher' => null,
                'folder' => null,
                'source' => null,
                'guid' => null,
                'language' => null,
                'installdate' => null,
                'bitswidth' => null,
                'filesize' => $object['Size'],
            );
        } else {
            //Android
            $data = array(
                'name' => $object['Name'],
                'version' => $object['Version'],
                'comments' => null,
                'publisher' => $object['Publisher'],
                'folder' => $object['InstallLocation'],
                'source' => null,
                'guid' => null,
                'language' => null,
                'installdate' => null,
                'bitswidth' => null,
                'filesize' => null,
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
            case 'source':
                $value = (integer) !$value;
                break;
            case 'installdate':
                $value = ($value ? $value->format('Y-m-d') : null);
                break;
        }
        return $value;
    }
}
