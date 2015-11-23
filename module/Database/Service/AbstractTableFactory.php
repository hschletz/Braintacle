<?php
/**
 * Abstract factory for table objects
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

Namespace Database\Service;

/**
 * Abstract factory for table objects
 *
 * When requesting a service "Database\Table\ClassName", this factory tries to
 * instantiate an object of the same name, injecting the service locator into
 * its constructor.
 */
class AbstractTableFactory implements \Zend\ServiceManager\AbstractFactoryInterface
{
    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function canCreateServiceWithName(
        \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator,
        $name,
        $requestedName
    )
    {
        return strpos($requestedName, 'Database\Table\\') === 0;
    }

    /**
     * @internal
     * @codeCoverageIgnore
     */
    public function createServiceWithName(
        \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator,
        $name,
        $requestedName
    )
    {
        $table = new $requestedName($serviceLocator);
        $table->initialize();
        return $table;
    }
}