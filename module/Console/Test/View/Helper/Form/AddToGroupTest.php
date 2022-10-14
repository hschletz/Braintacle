<?php

/**
 * Tests for the AddToGroup Helper
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

namespace Console\Test\View\Helper\Form;

use Console\Form\AddToGroup as AddToGroupForm;
use Console\View\Helper\Form\AddToGroup as AddToGroupHelper;
use Laminas\View\Renderer\PhpRenderer;
use PHPUnit\Framework\MockObject\MockObject;

class AddToGroupTest extends \Library\Test\View\Helper\AbstractTest
{
    protected function getHelperName()
    {
        return 'consoleFormAddToGroup';
    }

    public function testRender()
    {
        $form = $this->createStub(AddToGroupForm::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->expects($this->exactly(2))->method('__call')->withConsecutive(
            ['consoleScript', ['form_addtogroup.js']],
            ['consoleForm', [$form]]
        )->willReturnOnConsecutiveCalls($this->returnSelf(), 'rendered form');

        /** @var MockObject|AddToGroupHelper|callable */
        $helper = $this->createPartialMock(AddToGroupHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('rendered form', $helper($form));
    }
}
