<?php
/**
 * Factory for NADA interface
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Service;

/**
 * Factory for NADA interface
 *
 * This factory provides the "Database\Nada" service.
 *
 * @codeCoverageIgnore
 */
class NadaFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    /**
     * @internal
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $database = \Nada\Factory::getDatabase($container->get('Db'));
        if ($database->isSqlite()) {
            $database->emulatedDatatypes = array('bool', 'date', 'decimal', 'timestamp');
        } elseif ($database->isMySql()) {
            $database->emulatedDatatypes = array('bool');
        }
        return $database;
    }
}
