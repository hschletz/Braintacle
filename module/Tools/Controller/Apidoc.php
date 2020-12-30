<?php
/**
 * Apidoc controller
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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

use \Symfony\Component\Process\Process;

/**
 * Apidoc controller
 */
class Apidoc
{
    /**
     * Generate API documentation
     *
     * @param \ZF\Console\Route $route
     * @param \Laminas\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     * @codeCoverageIgnore
     */
    public function __invoke(\ZF\Console\Route $route, \Laminas\Console\Adapter\AdapterInterface $console)
    {
        $process = new Process(['tools/phpDocumentor'], \Library\Application::getPath());
        $process->run(function ($type, $buffer) use ($console) {
            $console->write($buffer);
        });
    }
}
