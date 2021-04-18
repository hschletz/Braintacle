<?php

/**
 * "software_installations" view.
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

use Database\Connection;
use Doctrine\DBAL\Schema\View;
use Laminas\ServiceManager\ServiceLocatorInterface;

/**
 * "software_installations" view.
 *
 * Joins the software names from the "software_definitions" table and sanitizes
 * column names.
 */
class Software extends \Database\AbstractTable
{
    const TABLE = 'software_installations';

    public function __construct(ServiceLocatorInterface $serviceLocator, Connection $connection = null)
    {
        $this->_hydrator = new \Database\Hydrator\Software();

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Client\Item\Software')
        );

        parent::__construct($serviceLocator, $connection);
    }

    /** {@inheritdoc} */
    public function delete($where)
    {
        // This is a view. Forward operation to underlying table.
        return $this->getServiceLocator()->get('Database\Table\SoftwareRaw')->delete($where);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function updateSchema($prune = false)
    {
        // Reimplementation to provide a view

        // Create/update softwareRaw table first because this view depends on it.
        $softwareRaw = $this->_serviceLocator->get('Database\Table\SoftwareRaw');
        $softwareRaw->updateSchema($prune);

        $schema = $this->connection->getSchemaManager();
        if (!$schema->hasView(static::TABLE)) {
            $logger = $this->_serviceLocator->get('Library\Logger');
            $logger->info("Creating view 'software_installations'");

            $query = $this->connection->createQueryBuilder();
            $query->select(
                's.id',
                'sd.name',
                's.hardware_id',
                's.version',
                's.comments AS comment',
                's.publisher',
                's.folder AS install_location',
                's.source AS is_hotfix',
                's.guid',
                's.language',
                's.installdate AS installation_date',
                's.bitswidth AS architecture',
                's.filesize AS size',
                'sd.display'
            )
            ->from(SoftwareRaw::TABLE, 's')
            ->leftJoin('s', SoftwareDefinitions::TABLE, 'sd', 'sd.id = s.definition_id');

            $view = new View(static::TABLE, $query->getSQL());
            $schema->createView($view);

            $logger->info('done.');
        }

        // Temporary workaround for tests
        $nada = $this->_serviceLocator->get('Database\Nada');
        if (!in_array(static::TABLE, $nada->getViewNames())) {
            $nada->createView(static::TABLE, $query->getSQL());
        }
    }
}
