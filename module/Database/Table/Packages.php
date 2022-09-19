<?php

/**
 * "download_available" table
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

namespace Database\Table;

/**
 * "download_available" table
 *
 * Produces \Model\Package\Package result sets.
 */
class Packages extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'download_available';

        $map = array(
            'name' => 'Name',
            'fileid' => 'Id',
            'priority' => 'Priority',
            'fragments' => 'NumFragments',
            'size' => 'Size',
            'osname' => 'Platform',
            'comment' => 'Comment',
            'num_pending' => 'NumPending',
            'num_running' => 'NumRunning',
            'num_success' => 'NumSuccess',
            'num_error' => 'NumError',
        );
        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy($map)
        );
        $this->_hydrator->addFilter('whitelist', new \Library\Hydrator\Filter\Whitelist($map));

        $platform = new \Database\Hydrator\Strategy\Packages\Platform();
        $this->_hydrator->addStrategy('Platform', $platform);
        $this->_hydrator->addStrategy('osname', $platform);

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Package\Package')
        );
        parent::__construct($serviceLocator);
    }
}
