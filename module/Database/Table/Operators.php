<?php

/**
 * "operators" table
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

namespace Database\Table;

use Model\Operator\Operator;

/**
 * "operators" table
 */
class Operators extends \Database\AbstractTable
{
    const TABLE = 'operators';

    /**
     * Indicator for legacy (MD5) hash
     */
    const HASH_LEGACY = 0;

    /**
     * Indicator for password_hash() default hash type
     */
    const HASH_DEFAULT = 1;

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $map = array(
            'id' => 'Id',
            'firstname' => 'FirstName',
            'lastname' => 'LastName',
            'email' => 'MailAddress',
            'comments' => 'Comment',
        );
        $this->_hydrator = new \Laminas\Hydrator\ArraySerializableHydrator();
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy($map)
        );
        $this->_hydrator->addFilter('whitelist', new \Library\Hydrator\Filter\Whitelist($map));

        $this->resultSetPrototype = new \Laminas\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Operator\Operator')
        );
        parent::__construct($serviceLocator);
    }

    public function getPrototype(): Operator
    {
        return $this->getServiceLocator()->get(Operator::class);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function preSetSchema(array $schema, bool $prune): void
    {
        $logger = $this->connection->getLogger();
        $schemaManager = $this->connection->getSchemaManager();

        // Drop non-admin accounts
        $logger->debug('Checking for non-admin accounts.');
        if ($schemaManager->tablesExist([static::TABLE])) {
            $dropped = 0;
            $columns = $schemaManager->listTableColumns(static::TABLE);
            if (isset($columns['accesslvl'])) {
                $dropped += $this->delete(new \Laminas\Db\Sql\Predicate\Operator('accesslvl', '!=', 1));
            }
            if (isset($columns['new_accesslvl'])) {
                $dropped += $this->delete(new \Laminas\Db\Sql\Predicate\Operator('new_accesslvl', '!=', 'sadmin'));
            }
            if ($dropped) {
                $logger->warn("$dropped non-admin accounts dropped.");
            }
        }
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function setSchema(array $schema, bool $prune): void
    {
        $database = $this->_serviceLocator->get('Database\Nada');
        $index = array_search('password_version', array_column($schema['columns'], 'name'));
        if (
            in_array($this->table, $database->getTableNames()) and
            !array_key_exists('password_version', $database->getTable($this->table)->getColumns())
        ) {
            $schema['columns'][$index]['notnull'] = false;
        }
        parent::setSchema($schema, $prune);

        if ($schema['columns'][$index]['notnull'] == false) {
            $logger = $this->_serviceLocator->get('Library\Logger');
            $logger->info('Setting legacy hash type on existing accounts');
            $this->update(array('password_version' => self::HASH_LEGACY));
            $schema['columns'][$index]['notnull'] = true;
            parent::setSchema($schema, $prune);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function postSetSchema(array $schema, bool $prune): void
    {
        $logger = $this->connection->getLogger();
        $logger->debug('Checking for existing account.');
        if ($this->connection->executeQuery('SELECT * FROM ' . static::TABLE)->fetchOne() == 0) {
            // No account exists yet, create a default account.
            $this->_serviceLocator->get('Model\Operator\OperatorManager')->createOperator(
                array('Id' => 'admin'),
                'admin'
            );
            $logger->notice(
                'Default account \'admin\' created with password \'admin\'.'
            );
        }

        // Warn about default password 'admin'
        $logger->debug('Checking for accounts with default password.');
        $md5Default = md5('admin');
        $query = $this->connection->createQueryBuilder();
        $query->select('id', 'passwd', 'password_version')->from(static::TABLE);
        foreach ($query->execute()->iterateAssociative() as $operator) {
            if ($operator['password_version'] == self::HASH_LEGACY) {
                $logger->warn(
                    sprintf(
                        'Account "%s" has an unsafe hash and should log in to have it automatically updated.',
                        $operator['id']
                    )
                );
            }
            if (
                ($operator['password_version'] == self::HASH_LEGACY and $operator['passwd'] == $md5Default) or
                ($operator['password_version'] == self::HASH_DEFAULT and password_verify('admin', $operator['passwd']))
            ) {
                $logger->warn(
                    sprintf(
                        'Account "%s" has default password. It should be changed as soon as possible!',
                        $operator['id']
                    )
                );
            }
        }
    }
}
