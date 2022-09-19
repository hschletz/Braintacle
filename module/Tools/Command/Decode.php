<?php

/**
 * Decode a compressed inventory file
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
use Symfony\Component\Console\Input\InputArgument;

/**
 * Decode a compressed inventory file
 *
 * @codeCoverageIgnore
 */
class Decode extends Command
{
    protected static $defaultName = 'decode';

    protected function configure()
    {
        $this->setDescription('Decodes a compressed inventory file as created by agents');
        $this->addArgument('input file', InputArgument::REQUIRED, 'compressed input file');
        $this->addArgument('output file', InputArgument::OPTIONAL, 'XML output file (default: print to STDOUT)');
    }
}
