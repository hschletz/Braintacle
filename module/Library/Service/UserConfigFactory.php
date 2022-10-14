<?php

/**
 * Factory for Config from INI file
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

namespace Library\Service;

use ArrayObject;

/**
 * Factory for user config from INI file
 *
 * Returns the Library\UserConfig entry from the ApplicationConfig service. If
 * it does not exist or is not an array, the following locations are searched
 * for a user config file and the first one found is read and its content
 * returned:
 *
 * 1. The path set in the Library\UserConfig service (if it is a string).
 * 2. The path set in the BRAINTACLE_CONFIG environment variable (if the
 *    variable exists).
 * 3. Fall back to config/braintacle.ini relative to the Braintacle root
 *    directory.
 */
class UserConfigFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    /** {@inheritdoc} */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $userConfig = null;
        $applicationConfig = $container->get('ApplicationConfig');
        if (isset($applicationConfig['Library\UserConfig'])) {
            $userConfig = $applicationConfig['Library\UserConfig'];
            if ($userConfig instanceof ArrayObject) {
                return $userConfig;
            }
        }
        if (!$userConfig) {
            $userConfig = getenv('BRAINTACLE_CONFIG');
        }
        if (!$userConfig) {
            $userConfig = \Library\Application::getPath('config/braintacle.ini');
        }
        $reader = new \Laminas\Config\Reader\Ini();
        return new ArrayObject($reader->fromFile($userConfig));
    }
}
