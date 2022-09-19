<?php

/**
 * Hydrator for Software item
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

namespace Protocol\Hydrator;

/**
 * Hydrator for Software item
 */
class Software extends DatabaseProxy
{
    /**
     * Map of protocol identifiers to database identifiers (if different)
     * @var string[]
     */
    protected $map = [
        'COMMENTS' => 'COMMENT',
        'FOLDER' => 'INSTALL_LOCATION',
        'SOURCE' => 'IS_HOTFIX',
        'INSTALLDATE' => 'INSTALLATION_DATE',
        'BITSWIDTH' => 'ARCHITECTURE',
        'FILESIZE' => 'SIZE',
    ];

    /** @inheritdoc */
    public function __construct(\Database\Hydrator\Software $hydrator)
    {
        // Just call parent constructor. Overridden only to enforce specific hydrator class.
        parent::__construct($hydrator);
    }

    /**
     * {@inheritdoc}
     * @throws \LogicException because this is not implemented
     */
    public function hydrate(array $data, $object)
    {
        throw new \LogicException('not implemented');
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = parent::extract($object);

        // Replace names that differ
        foreach ($this->map as $protocolName => $databaseName) {
            $data[$protocolName] = $data[$databaseName];
            unset($data[$databaseName]);
        }

        // Protocol uses nonstandard date format
        if ($data['INSTALLDATE']) {
            $data['INSTALLDATE'] = \DateTime::createFromFormat('Y-m-d', $data['INSTALLDATE'])->format('Y/m/d');
        }

        return $data;
    }
}
