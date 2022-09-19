<?php

/**
 * Build a package
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

namespace Tools\Controller;

use Model\Config;
use Model\Package\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build a package
 */
class Build implements ControllerInterface
{
    protected $config;
    protected $packageManager;

    public function __construct(Config $config, PackageManager $packageManager)
    {
        $this->config = $config;
        $this->packageManager = $packageManager;
    }

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $file = $input->getArgument('file');

        $this->packageManager->buildPackage(
            [
                'Name' => $name,
                'Comment' => null,
                'FileName' => basename($file),
                'FileLocation' => $file,
                'Priority' => $this->config->defaultPackagePriority,
                'Platform' => $this->config->defaultPlatform,
                'DeployAction' => $this->config->defaultAction,
                'ActionParam' => $this->config->defaultActionParam,
                'Warn' => $this->config->defaultWarn,
                'WarnMessage' => $this->config->defaultWarnMessage,
                'WarnCountdown' => $this->config->defaultWarnCountdown,
                'WarnAllowAbort' => $this->config->defaultWarnAllowAbort,
                'WarnAllowDelay' => $this->config->defaultWarnAllowDelay,
                'PostInstMessage' => $this->config->defaultPostInstMessage,
                'MaxFragmentSize' => $this->config->defaultMaxFragmentSize,
            ],
            false
        );
        $output->writeln('Package successfully built.');

        return Command::SUCCESS;
    }
}
