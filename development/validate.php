#!/usr/bin/php
<?php

/**
 * Validate code formatting
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

error_reporting(-1);

require_once __DIR__ . '/../vendor/autoload.php';

$cmd = [
    (new \Symfony\Component\Process\PhpExecutableFinder())->find(),
    \Library\Application::getPath('vendor/bin/phpcs'),
    '-n', // suppress warnings
    '--report-width=120',
    '--standard=PSR12',
    '--extensions=php',
    \Library\Application::getPath('development'),
    \Library\Application::getPath('module'),
    \Library\Application::getPath('public'),
];
$process = new \Symfony\Component\Process\Process($cmd);
$process->start();
foreach ($process as $type => $data) {
    print $data;
}
exit($process->getExitCode());
