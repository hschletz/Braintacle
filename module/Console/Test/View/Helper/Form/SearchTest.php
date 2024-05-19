<?php

/**
 * Tests for the Search Helper
 *
 * Copyright (C) 2011-2024 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\Search as SearchForm;
use Console\View\Helper\Form\Search as SearchHelper;
use Laminas\View\Renderer\PhpRenderer;
use Library\Test\View\Helper\AbstractTestCase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\MockObject\MockObject;

class SearchTest extends AbstractTestCase
{
    use MockeryPHPUnitIntegration;

    protected function getHelperName()
    {
        return 'consoleFormSearch';
    }

    public function testRender()
    {
        $form = $this->createStub(SearchForm::class);

        $view = Mockery::mock(PhpRenderer::class);
        $view->shouldReceive('consoleScript')->once()->with('form_search.js');
        $view->shouldReceive('consoleForm')->once()->with($form)->andReturn('rendered form');

        /** @var MockObject|SearchHelper|callable */
        $helper = $this->createPartialMock(SearchHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('rendered form', $helper($form));
    }
}
