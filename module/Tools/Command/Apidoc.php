<?php

/**
 * phpDocumentor wrapper
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

namespace Tools\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * phpDocumentor wrapper
 *
 * @codeCoverageIgnore
 */
class Apidoc extends Command
{
    protected static $defaultName = 'apidoc';

    protected function configure()
    {
        $this->setDescription('Generates API documentation in the build/api directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $process = new Process(['tools/phpDocumentor'], \Library\Application::getPath());
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer, false, OutputInterface::OUTPUT_RAW);
        });

        return Command::SUCCESS;
    }
}
