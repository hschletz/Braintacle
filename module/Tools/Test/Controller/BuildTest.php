<?php

/**
 * Tests for Build controller
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

namespace Tools\Test\Controller;

use Model\Config;
use Model\Package\PackageManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        /** @var Config|Stub */
        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnArgument(0);

        /** @var PackageManager|MockObject */
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->once())->method('buildPackage')->with(
            [
                'Name' => 'packageName',
                'Comment' => null,
                'FileName' => 'fileName',
                'FileLocation' => 'path/fileName',
                'Priority' => 'defaultPackagePriority',
                'Platform' => 'defaultPlatform',
                'DeployAction' => 'defaultAction',
                'ActionParam' => 'defaultActionParam',
                'Warn' => 'defaultWarn',
                'WarnMessage' => 'defaultWarnMessage',
                'WarnCountdown' => 'defaultWarnCountdown',
                'WarnAllowAbort' => 'defaultWarnAllowAbort',
                'WarnAllowDelay' => 'defaultWarnAllowDelay',
                'PostInstMessage' => 'defaultPostInstMessage',
                'MaxFragmentSize' => 'defaultMaxFragmentSize',
            ],
            false
        );

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['name', 'packageName'],
            ['file', 'path/fileName'],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('Package successfully built.');

        $controller = new \Tools\Controller\Build($config, $packageManager);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));
    }
}
