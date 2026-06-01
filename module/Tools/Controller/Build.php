<?php

/**
 * Build a package
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Package\Action;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\SourceFileFactory;
use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Model\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Build a package
 */
class Build implements ControllerInterface
{
    public function __construct(
        private Config $config,
        private SourceFileFactory $sourceFileFactory,
        private Builder $builder,
    ) {}

    public function __invoke(InputInterface $input, OutputInterface $output)
    {
        $package = new Package();
        $package->name = $input->getArgument('name');
        $package->comment = null;
        $package->platform = Platform::from($this->config->defaultPlatform);
        $package->action = Action::from($this->config->defaultAction);
        $package->actionParam = $this->config->defaultActionParam;
        $package->priority = $this->config->defaultPackagePriority;
        $package->maxFragmentSize = $this->config->defaultMaxFragmentSize;
        $package->warn = $this->config->defaultWarn;
        $package->warnMessage = $this->config->defaultWarnMessage;
        $package->warnCountdown = $this->config->defaultWarnCountdown;
        $package->warnAllowAbort = $this->config->defaultWarnAllowAbort;
        $package->warnAllowDelay = $this->config->defaultWarnAllowDelay;
        $package->postInstMessage = $this->config->defaultPostInstMessage;

        $file = $input->getArgument('file');
        $sourceFile = $this->sourceFileFactory->fromPath($file);

        $this->builder->build($package, $sourceFile, false);
        $output->writeln('Package successfully built.');

        return Command::SUCCESS;
    }
}
