<?php

/**
 * Hydrator for filesystems
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

namespace Database\Hydrator;

/**
 * Hydrator for filesystems
 *
 * Sanitizes incompatible structures produced by different agents and calculates
 * UsedSpace property on hydration.
 */
class Filesystems implements \Laminas\Hydrator\HydratorInterface
{
    /**
     * Map for hydrateName()
     *
     * Names that cannot be hydrated unambigously are not provided.
     *
     * @var string[]
     */
    protected $_hydratorMap = array(
        'letter' => 'Letter',
        'createdate' => 'CreationDate',
        'filesystem' => 'Filesystem',
        'total' => 'Size',
        'free' => 'FreeSpace',
    );

    /**
     * Map for extractName()
     * @var string[]
     */
    protected $_extractorMap = array(
        'Letter' => 'letter',
        'Type' => 'type',
        'Label' => 'volumn',
        'Mountpoint' => 'type',
        'Device' => 'volumn',
        'CreationDate' => 'createdate',
        'Filesystem' => 'filesystem',
        'Size' => 'total',
        'FreeSpace' => 'free',
    );

    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        if ($data['letter']) {
            // Windows
            $object->Letter = $this->hydrateValue('Letter', $data['letter']);
            $object->Type = $data['type'];
            $object->Label = $data['volumn'];
        } else {
            // UNIX
            $object->Mountpoint = $data['type'];
            $object->Device = $data['volumn'];
            $object->CreationDate = $this->hydrateValue('CreationDate', $data['createdate']);
        }
        $object->Filesystem = $data['filesystem'];
        $object->Size = $data['total'];
        $object->FreeSpace = $data['free'];
        $object->UsedSpace = $data['total'] - $data['free'];
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = array();
        if (isset($object->Letter)) {
            // Windows
            $data['letter'] = $this->extractValue('letter', $object->Letter);
            $data['type'] = $object->Type;
            $data['volumn'] = $object->Label;
            $data['createdate'] = null;
        } else {
            // UNIX
            $data['letter'] = null;
            $data['type'] = $object->Mountpoint;
            $data['volumn'] = $object->Device;
            $data['createdate'] = $this->extractValue('createdate', $object->CreationDate);
        }
        $data['filesystem'] = $object->Filesystem;
        $data['total'] = $object->Size;
        $data['free'] = $object->FreeSpace;
        return $data;
    }

    /**
     * Hydrate name
     *
     * Only names that don't require context can be hydrated with this method.
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
            case 'Letter':
                // Remove slash appended by older agents. This is not reverted
                // on extraction.
                $value = rtrim($value, '/');
                break;
            case 'CreationDate':
                if ($value) {
                    $value = \DateTime::createFromFormat('Y-m-d', $value);
                    $value->setTime(0, 0);
                } else {
                    $value = null;
                }
                break;
        }
        return $value;
    }

    /**
     * Extract value
     */
    public function extractValue(string $name, $value)
    {
        if ($name == 'createdate') {
            $value = ($value ? $value->format('Y-m-d') : null);
        }
        return $value;
    }
}
