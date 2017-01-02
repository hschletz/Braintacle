<?php
/**
 * Apidoc controller
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
 *
 * @package Tools
 */

namespace Tools\Controller;

/**
 * Apidoc controller
 */
class Apidoc
{
    /**
     * Generate API documentation
     *
     * @param \ZF\Console\Route $route
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     * @codeCoverageIgnore
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $cmd = array(
            escapeshellarg(\Library\Application::getPath('vendor/bin/apigen')),
            'generate',
            '-s ' . escapeshellarg(\Library\Application::getPath('module')),
            '-d ' . escapeshellarg(\Library\Application::getPath('doc') . DIRECTORY_SEPARATOR . 'api'),
            '--deprecated',
            '--todo',
            '--php',
            '--exclude ' . escapeshellarg('*/Test'),
            '--title ' . escapeshellarg('Braintacle API documentation'),
        );
        $cmd = implode(' ', $cmd);
        passthru($cmd, $result); // system() would swallow or delay some output
        if ($result) {
            $console->writeLine("ERROR: ApiGen returned with error code $result. Command line was:");
            $console->writeLine();
            $console->writeLine($cmd);
            $console->writeLine();
            return 1;
        }
    }
}
