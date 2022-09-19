<?php

/**
 * Tools Application
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
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Tools Application
 *
 * @codeCoverageIgnore
 */
class Application extends \Symfony\Component\Console\Application
{
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct('Braintacle command line tool');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(ConsoleEvents::COMMAND, new ConfigListener($serviceManager), 1);
        $dispatcher->addListener(ConsoleEvents::COMMAND, new ControllerInjectionListener($serviceManager));
        $this->setDispatcher($dispatcher);

        $this->add(new Command\Apidoc());
        $this->add(new Command\Build());
        $this->add(new Command\Database());
        $this->add(new Command\Decode());
        $this->add(new Command\Export());
        $this->add(new Command\Import());
    }

    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Alternative config file'));

        return $definition;
    }
}
