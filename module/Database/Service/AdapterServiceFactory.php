<?php
/**
 * Database adapter service
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

Namespace Database\Service;

/**
 * Database adapter service
 */
class AdapterServiceFactory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function createService(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $config = $config['db'];

        // The Pdo_Pgsql driver does not support the "charset" option.
        $isPdoPgsql = (strcasecmp($config['driver'], 'pdo_pgsql') == 0);
        if (!$isPdoPgsql) {
            $config['charset'] = 'utf8';
        }
        $adapter = new \Zend\Db\Adapter\Adapter($config);
        if ($isPdoPgsql) {
            $adapter->query("SET NAMES 'utf8'", \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
        }
        return $adapter;
    }
}
