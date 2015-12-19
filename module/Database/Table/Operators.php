<?php
/**
 * "operators" table
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 * "operators" table
 */
class Operators extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $map = array(
            'id' => 'Id',
            'firstname' => 'FirstName',
            'lastname' => 'LastName',
            'email' => 'MailAddress',
            'comments' => 'Comment',
        );
        $this->_hydrator = new \Zend\Stdlib\Hydrator\ArraySerializable;
        $this->_hydrator->setNamingStrategy(
            new \Database\Hydrator\NamingStrategy\MapNamingStrategy($map)
        );
        $this->_hydrator->addFilter('whitelist', new \Library\Hydrator\Filter\Whitelist($map));

        $this->resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet(
            $this->_hydrator,
            $serviceLocator->get('Model\Operator\Operator')
        );
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _preSetSchema($logger, $schema, $database)
    {
        // Drop non-admin accounts
        $logger->debug('Checking for non-admin accounts.');
        if (in_array($this->table, $database->getTableNames())) {
            $dropped = 0;
            $columns = $database->getTable($this->table)->getColumns();
            if (isset($columns['accesslvl'])) {
                $dropped += $this->delete(new \Zend\Db\Sql\Predicate\Operator('accesslvl', '!=', 1));
            }
            if (isset($columns['new_accesslvl'])) {
                $dropped += $this->delete(new \Zend\Db\Sql\Predicate\Operator('new_accesslvl', '!=', 'sadmin'));
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
    protected function _postSetSchema($logger, $schema, $database)
    {
        // If no account exists yet, create a default account.
        $logger->debug('Checking for existing account.');
        if ($this->select()->count() == 0) {
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
        if ($this->select(array('passwd' => md5('admin')))->count() > 0) {
            $logger->warn(
                'Account with default password detected. It should be changed as soon as possible!'
            );
        }
    }
}
