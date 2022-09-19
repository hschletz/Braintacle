<?php

/**
 * Manage database schema
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
use Symfony\Component\Console\Input\InputOption;

/**
 * Manage database schema
 *
 * @codeCoverageIgnore
 */
class Database extends Command
{
    protected static $defaultName = 'database';

    protected function configure()
    {
        $this->setDescription('Updates the database');
        $this->addOption(
            'loglevel',
            'l',
            InputOption::VALUE_REQUIRED,
            'maximum log level (emerg|alert|crit|err|warn|notice|info|debug)',
            'info'
        );
        $this->addOption(
            'prune',
            'p',
            InputOption::VALUE_NONE,
            'Drop obsolete tables and columns (default: just warn)'
        );
    }
}
