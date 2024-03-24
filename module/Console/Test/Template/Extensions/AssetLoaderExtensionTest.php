<?php

namespace Console\Test\Template\Extensions;

use Console\Template\Extensions\AssetLoaderExtension;
use Console\View\Helper\ConsoleScript;
use Latte\Engine;
use Latte\Loaders\StringLoader;
use PHPUnit\Framework\TestCase;

class AssetLoaderExtensionTest extends TestCase
{
    public function testAddScript()
    {
        /** @var ConsoleScript */
        $consoleScriptHelper = $this->createMock(ConsoleScript::class);
        $consoleScriptHelper->expects($this->once())->method('__invoke')->with('script.js');

        $engine = new Engine();
        $engine->addExtension(new AssetLoaderExtension($consoleScriptHelper));
        $engine->setLoader(new StringLoader(['test' => 'foo{addScript script.js}bar']));
        $this->assertEquals('foobar', $engine->renderToString('test'));
    }
}
