<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use Console\Template\Functions\TranslateFunction;
use Console\Template\TemplateLoader;
use Latte\Engine;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TemplateEngineTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testRender()
    {
        $templateLoader = $this->createStub(TemplateLoader::class);
        $assetUrlFunction = $this->createStub(AssetUrlFunction::class);
        $csrfTokenFunction = $this->createStub(CsrfTokenFunction::class);
        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $translateFunction = $this->createStub(TranslateFunction::class);

        $engine = Mockery::mock(Engine::class);
        $engine->shouldReceive('setLoader')->once()->with($templateLoader);
        $engine->shouldReceive('addFunction')->once()->with('assetUrl', $assetUrlFunction);
        $engine->shouldReceive('addFunction')->once()->with('csrfToken', $csrfTokenFunction);
        $engine->shouldReceive('addFunction')->once()->with('pathForRoute', $pathForRouteFunction);
        $engine->shouldReceive('addFunction')->once()->with('translate', $translateFunction);

        $engine->shouldReceive('renderToString')->with('template', ['key' => 'value'])->andReturn('content');

        $templateEngine = new TemplateEngine(
            $engine,
            $templateLoader,
            $assetUrlFunction,
            $csrfTokenFunction,
            $pathForRouteFunction,
            $translateFunction,
        );
        $this->assertEquals('content', $templateEngine->render('template', ['key' => 'value']));
    }
}
