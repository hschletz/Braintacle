<?php
/**
 * Factory for Config from INI file
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

/**
 * Factory for Config from INI file
 *
 * Returns the Library\UserConfig entry from the ApplicationConfig service. If
 * it does not exist, the following locations are searched for a user config
 * file and the first one found is read and its content returned:
 *
 * 1. The path set in the BRAINTACLE_CONFIG environment variable (if the
 *    variable exists).
 * 2. Fall back to user_config/braintacle.ini relative to the Braintacle root
 *    directory.
 *
 * @codeCoverageIgnore
 */
class UserConfigFactory implements \Zend\ServiceManager\Factory\FactoryInterface
{
    /**
     * @internal
     */
    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $applicationConfig = $container->get('ApplicationConfig');
        if (isset($applicationConfig['Library\UserConfig'])) {
            return $applicationConfig['Library\UserConfig'];
        } else {
            $reader = new \Zend\Config\Reader\Ini;
            return $reader->fromFile(
                getenv('BRAINTACLE_CONFIG') ?: (\Library\Application::getPath('user_config/braintacle.ini'))
            );
        }
    }
}
