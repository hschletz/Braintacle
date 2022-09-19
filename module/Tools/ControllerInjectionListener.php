<?php

/**
 * Listener for injection of the controller service
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

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Listener for injection of the controller service
 *
 * To be attached to ConsoleEvents::COMMAND. If a service with the name
 * "Tools\command:command_name" exists, it will get instantiated and attached
 * via setCode().
 *
 * Commands which consume services from a container should not implement the
 * execute() method, but have that extra service injected instead. The
 * dependencies should be injected into the controller service.
 *
 * The Command class should not consume any services because instantiation can
 * have side effects like attempting a database connection. Command classes can
 * be instantiated even when the command is not executed. For example, the
 * global "list" command will instantiate all commands to read their
 * configuration. The mentioned side effects may cause failure where the
 * provided functionality is not even needed.
*/
class ControllerInjectionListener
{
    /**
     * Container
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(ConsoleCommandEvent $event)
    {
        $command = $event->getCommand();
        $serviceName = 'Tools\command:' . $command->getName();
        if ($this->container->has($serviceName)) {
            $controller = $this->container->get($serviceName);
            $command->setCode($controller);
        }
    }
}
