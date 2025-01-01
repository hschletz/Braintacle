<?php

/**
 * Tests for the ConsoleScript helper
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\View\Helper;

use Braintacle\Template\Function\AssetUrlFunction;
use Console\View\Helper\ConsoleScript;
use Laminas\Escaper\Escaper;
use Library\Test\View\Helper\AbstractTestCase;

class ConsoleScriptTest extends AbstractTestCase
{
    public function testHelperService()
    {
        $this->assertInstanceOf(ConsoleScript::class, $this->getHelper(ConsoleScript::class));
    }

    public function testGetHtml()
    {
        $assetUrl = $this->createMock(AssetUrlFunction::class);
        $assetUrl->method('__invoke')->with('script_path')->willReturn('asset_url');

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtmlAttr')->with('asset_url')->willReturn('escaped');

        $consoleScript = new ConsoleScript($assetUrl, $escaper);

        $this->assertEquals(
            '<script src="escaped" type="module"></script>',
            $consoleScript('script_path')
        );
    }
}
