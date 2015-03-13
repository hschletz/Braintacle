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

Namespace Database\Table;

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
        $this->_hydrator = new \Zend\Stdlib\Hydrator\ArraySerializable;
        $this->_hydrator->setNamingStrategy(new \Database\Hydrator\NamingStrategy\Operators);

        $this->resultSetPrototype = new \Zend\Db\ResultSet\HydratingResultSet(
            $this->_hydrator, $serviceLocator->get('Model\Operator\Operator')
        );
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _postSetSchema($logger, $schema, $database)
    {
        // If no account exists yet, create a default account.
        $logger->debug('Checking for existing account.');
        if (
            $this->adapter->query(
                'SELECT COUNT(id) AS num FROM operators',
                \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            )->current()->offsetGet('num') === '0'
        ) {
            $this->_serviceLocator->get('Model\Operator\OperatorManager')->create(array('Id' => 'admin'), 'admin');
            $logger->notice(
                'Default account \'admin\' created with password \'admin\'.'
            );
        }

        // Warn about default password 'admin'
        $logger->debug('Checking for accounts with default password.');
        if (
            $this->adapter->query(
                'SELECT COUNT(id) AS num FROM operators WHERE passwd = ?',
                array(md5('admin'))
            )->current()->offsetGet('num') !== '0'
        ) {
            $logger->warn(
                'Account with default password detected. It should be changed as soon as possible!'
            );
        }
    }
}
