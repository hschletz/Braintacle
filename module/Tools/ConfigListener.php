<?php

/**
 * Listener for evaluating config option
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

namespace Tools;

use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Listener for evaluating --config option
 *
 * To be attached to ConsoleEvents::COMMAND, it has access to the parsed command
 * line, but gets invoked before the command. It should be invoked as early as
 * possible (attach it with a higher priority than any other listeners) so that
 * the application config is correctly set before it gets evaluated anywhere
 * else.
 */
class ConfigListener
{
    /**
     * Service Manager
     * @var ServiceManager
     */
    protected $serviceManager;

    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    public function __invoke(ConsoleCommandEvent $event)
    {
        $input = $event->getInput();
        $config = $input->getOption('config');
        if ($config) {
            // Set Library\UserConfig to specified config file
            $applicationConfig = $this->serviceManager->get('ApplicationConfig');
            if (isset($applicationConfig['Library\UserConfig'])) {
                throw new \LogicException('Library\UserConfig already set');
            }
            $applicationConfig['Library\UserConfig'] = $config;

            $allowOverride = $this->serviceManager->getAllowOverride();
            $this->serviceManager->setAllowOverride(true);
            $this->serviceManager->setService('ApplicationConfig', $applicationConfig);
            $this->serviceManager->setAllowOverride($allowOverride);
        }
    }
}
