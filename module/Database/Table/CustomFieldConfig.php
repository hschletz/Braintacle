<?php
/**
 * "accountinfo_config" table
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * "accountinfo_config" table
 */
class CustomFieldConfig extends \Database\AbstractTable
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $this->table = 'accountinfo_config';
        parent::__construct($serviceLocator);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected function _postSetSchema()
    {
        $logger = $this->_serviceLocator->get('Library\Logger');

        // If table is empty, create default entries
        $logger->debug('Checking for existing custom field config.');
        if (
            $this->adapter->query(
                'SELECT COUNT(id) AS num FROM accountinfo_config',
                \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE
            )->current()->offsetGet('num') === '0'
        ) {
            $this->insert(
                array(
                    'name' => 'TAG',
                    'type' => 0,
                    'account_type' => 'COMPUTERS',
                    'show_order' => 1,
                )
            );
            $this->insert(
                array(
                    'name' => 'TAG',
                    'type' => 0,
                    'account_type' => 'SNMP',
                    'show_order' => 1,
                )
            );
            $logger->info(
                'Default custom field config created.'
            );
        }
    }
}
