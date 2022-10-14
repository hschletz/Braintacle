<?php

/**
 * Tests for the ConsoleScript helper
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

namespace Console\Test\View\Helper;

use ArrayIterator;
use Console\View\Helper\ConsoleScript;
use Laminas\Uri\UriInterface;
use Laminas\View\Helper\Placeholder\Container\AbstractContainer;
use Laminas\View\Renderer\PhpRenderer;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;

class ConsoleScriptTest extends \Library\Test\View\Helper\AbstractTest
{
    public function testInvokeNoArgs()
    {
        /** @var MockObject|ConsoleScript|callable */
        $consoleScript = $this->createPartialMock(ConsoleScript::class, ['getContainer']);
        $consoleScript->expects($this->never())->method('getContainer');

        $this->assertSame($consoleScript, $consoleScript());
    }

    public function testInvokeWithScript()
    {
        $container = $this->createMock(AbstractContainer::class);
        $container->expects($this->once())->method('append')->with('script');

        /** @var MockObject|ConsoleScript|callable */
        $consoleScript = $this->createPartialMock(ConsoleScript::class, ['getContainer']);
        $consoleScript->expects($this->once())->method('getContainer')->willReturn($container);

        $this->assertSame($consoleScript, $consoleScript('script'));
    }

    public function testToString()
    {
        $consoleScript = $this->createPartialMock(ConsoleScript::class, ['getIterator', '__call', 'getHtml']);
        $consoleScript->method('getIterator')->willReturn(new ArrayIterator(['script1', 'script2']));
        $consoleScript->method('__call')->with('getSeparator', [])->willReturn('_');
        $consoleScript->method('getHtml')
                      ->withConsecutive(['script1'], ['script2'])
                      ->willReturnOnConsecutiveCalls('html1', 'html2');

        $this->assertEquals('html1_html2', $consoleScript->toString());
    }

    public function testGetHtml()
    {
        $uri = $this->createStub(UriInterface::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->with('escapeHtmlAttr', [$uri])->willReturn('script_uri');

        $consoleScript = $this->createPartialMock(ConsoleScript::class, ['getUri', 'getView']);
        $consoleScript->method('getUri')->with('script_name')->willReturn($uri);
        $consoleScript->method('getView')->willReturn($view);

        $this->assertEquals(
            '<script src="script_uri" type="module"></script>',
            $consoleScript->getHtml('script_name')
        );
    }

    public function testGetUri()
    {
        $filename = vfsStream::newFile('test.js')->at(vfsStream::setup('root'))->url();

        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->with('basePath', ['/js/script.js'])->willReturn('script_path');

        $consoleScript = $this->createPartialMock(ConsoleScript::class, ['getFile', 'getView']);
        $consoleScript->method('getFile')->with('script.js')->willReturn($filename);
        $consoleScript->method('getView')->willReturn($view);

        $uri = 'script_path?' . filemtime($filename);
        $this->assertEquals($uri, $consoleScript->getUri('script.js'));
    }

    public function testGetFile()
    {
        $consoleScript = new ConsoleScript();
        $this->assertStringEndsWith('/public/js/form_search.js', $consoleScript->getFile('form_search.js'));
    }
}
