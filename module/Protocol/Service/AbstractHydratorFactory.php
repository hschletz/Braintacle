<?php

/**
 * Abstract factory for hydrators
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

namespace Protocol\Service;

/**
 * Abstract factory for hydrators
 *
 * When requesting a service "Protocol\Hydrator\TableName", this factory tries to
 * instantiate a \Protocol\Hydrator\DatabaseProxy hydrator set up with an
 * appropriate database hydrator.
 *
 * @codeCoverageIgnore
 */
class AbstractHydratorFactory implements \Laminas\ServiceManager\Factory\AbstractFactoryInterface
{
    /** {@inheritdoc} */
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        return strpos($requestedName, 'Protocol\Hydrator\\') === 0;
    }

    /** {@inheritdoc} */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        return new \Protocol\Hydrator\DatabaseProxy(
            clone $container->get(
                'Database\Table' . substr($requestedName, strrpos($requestedName, '\\'))
            )->getHydrator()
        );
    }
}
