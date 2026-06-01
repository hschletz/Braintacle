<?php

/**
 * Tests for Build controller
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

namespace Tools\Test\Controller;

use Braintacle\Package\Action;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\SourceFile;
use Braintacle\Package\Build\SourceFileFactory;
use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Model\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoke()
    {
        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnMap([
            ['defaultPlatform', 'linux'],
            ['defaultAction', 'execute'],
            ['defaultActionParam', 'actionParam'],
            ['defaultPackagePriority', '8'],
            ['defaultMaxFragmentSize', '42'],
            ['defaultWarn', '1'],
            ['defaultWarnMessage', 'warnMessage'],
            ['defaultWarnCountdown', '60'],
            ['defaultWarnAllowAbort', '0'],
            ['defaultWarnAllowDelay', '1'],
            ['defaultPostInstMessage', 'postInstMessage'],
        ]);

        $sourceFile = $this->createStub(SourceFile::class);
        $sourceFileFactory = $this->createMock(SourceFileFactory::class);
        $sourceFileFactory->method('fromPath')->with('path/fileName')->willReturn($sourceFile);

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())->method('build')->with(
            $this->callback(function (Package $package) {
                $this->assertEquals(
                    [
                        'name' => 'packageName',
                        'comment' => null,
                        'priority' => 8,
                        'platform' => Platform::Linux,
                        'action' => Action::Execute,
                        'actionParam' => 'actionParam',
                        'warn' => true,
                        'warnMessage' => 'warnMessage',
                        'warnCountdown' => 60,
                        'warnAllowAbort' => false,
                        'warnAllowDelay' => true,
                        'postInstMessage' => 'postInstMessage',
                        'maxFragmentSize' => 42,
                    ],
                    (array) $package,
                );
                return true;
            }),
            $sourceFile,
            false
        );

        $input = $this->createStub(InputInterface::class);
        $input->method('getArgument')->willReturnMap([
            ['name', 'packageName'],
            ['file', 'path/fileName'],
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('writeln')->with('Package successfully built.');

        $controller = new \Tools\Controller\Build($config, $sourceFileFactory, $builder);
        $this->assertSame(Command::SUCCESS, $controller($input, $output));
    }
}
