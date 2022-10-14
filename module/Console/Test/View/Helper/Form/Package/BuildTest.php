<?php

/**
 * Tests for the Build Helper
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

namespace Console\Test\View\Helper\Form\Package;

use Console\Form\Package\Build as BuildForm;
use Console\View\Helper\Form\Package\Build as BuildHelper;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\MockObject\MockObject;

class BuildTest extends \Library\Test\View\Helper\AbstractTest
{
    protected function getHelperName()
    {
        return 'consoleFormPackageBuild';
    }

    public function testInvoke()
    {
        $form = $this->createStub(BuildForm::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->expects($this->exactly(2))->method('__call')->withConsecutive(
            ['consoleScript', ['form_package.js']],
            ['consoleForm', [$form]]
        )->willReturnOnConsecutiveCalls(null, 'rendered form');

        /** @var MockObject|BuildHelper|callable */
        $helper = $this->createPartialMock(BuildHelper::class, ['getView', 'initLabels']);
        $helper->method('getView')->willReturn($view);
        $helper->expects($this->once())->method('initLabels')->with($form);

        $this->assertEquals('rendered form', $helper($form));
    }

    public function initLabelsProvider()
    {
        return [
            ['launch', 'Command line'],
            ['execute', 'Command line'],
            ['store', 'Target path'],
        ];
    }

    /** @dataProvider initLabelsProvider */
    public function testInitLabels(string $action, string $expectedLabel)
    {
        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->willReturnMap([
            ['translate', ['Command line'], 'command_line'],
            ['translate', ['Target path'], 'target_path'],
        ]);

        $actionParam = $this->createMock(Text::class);
        $actionParam->expects($this->once())->method('setAttribute')->with(
            'data-labels',
            json_encode(['launch' => 'command_line', 'execute' => 'command_line', 'store' => 'target_path'])
        );
        $actionParam->expects($this->once())->method('setLabel')->with($expectedLabel);

        $deployAction = $this->createStub(Select::class);
        $deployAction->method('getValue')->willReturn($action);

        $form = $this->createMock(BuildForm::class);
        $form->method('get')->willReturnMap([
            ['ActionParam', $actionParam],
            ['DeployAction', $deployAction],
        ]);

        $helper = $this->createPartialMock(BuildHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $helper->initLabels($form);
    }
}
