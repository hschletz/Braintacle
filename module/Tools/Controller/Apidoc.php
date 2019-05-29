<?php
/**
 * Apidoc controller
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     * @codeCoverageIgnore
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $console->writeLine('Running Doxygen to generate documentation for dependencies...');

        // Delete tagfile if it exists. Otherwise it wouldn't be used reliably.
        try {
            unlink(\Library\Application::getPath('doxygen/dependencies.tag'));
        } catch (\Exception $e) {
            // No error if it doesn't exist
        }
        $process = new Process(['doxygen', \Library\Application::getPath('doxygen/dependencies.Doxyfile')]);
        $process->run(); // Ignore warnings

        $console->writeLine('Running Doxygen to generate Braintacle API documentation...');

        $process = new Process(['doxygen', \Library\Application::getPath('doxygen/braintacle.Doxyfile')]);
        $process->run(function ($type, $buffer) use ($console) {
            if (strpos($buffer, '<unknown>:1: warning: Detected potential recursive class relation') !== 0 and
                strpos($buffer, 'warning: Internal inconsistency: scope for class') === false and
                !preg_match('/^deprecated:\d+: warning: Illegal command \w+ as part of a <dt> tag/', $buffer)
            ) {
                $console->write($buffer);
            }
        });

        $console->writeLine('Postprocessing documentation...');

        $ignoreMessage = 'WARNING: could not parse ' . \Library\Application::getPath('doc/api/dependencies');
        $process = new Process([
            (new \Symfony\Component\Process\PhpExecutableFinder)->find(),
            \Library\Application::getPath('vendor/bin/doxygen-phpdoc-fixhtml.php'),
            \Library\Application::getPath('doc/api')
        ]);
        $process->run(function ($type, $buffer) use ($console, $ignoreMessage) {
            if (strpos($buffer, $ignoreMessage) !== 0) {
                $console->write($buffer);
            }
        });
        $console->writeLine('Done.');
    }
}
