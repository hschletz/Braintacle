<?php

namespace Braintacle\Test\Template\Function;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\Function\TranslateFunction;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\ErrorHandlerTestTrait;
use ErrorException;
use Latte\Engine;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;

class TemplateEngineTest extends TestCase
{
    use ErrorHandlerTestTrait;
    use MockeryPHPUnitIntegration;

    private int $errorReporting;

    #[Before]
    public function setErrorReporting()
    {
        $this->errorReporting = error_reporting();
    }

    #[After]
    public function restoreErrorReporting()
    {
        error_reporting($this->errorReporting);
    }

    public function testEngineConfig()
    {
        $templateLoader = $this->createStub(TemplateLoader::class);
        $assetUrlFunction = $this->createStub(AssetUrlFunction::class);
        $csrfTokenFunction = $this->createStub(CsrfTokenFunction::class);
        $pathForRouteFunction = $this->createStub(PathForRouteFunction::class);
        $translateFunction = $this->createStub(TranslateFunction::class);

        /** @var Mock|Engine */
        $engine = Mockery::mock(Engine::class);
        $engine->shouldReceive('setLocale')->once()->with('locale');
        $engine->shouldReceive('setLoader')->once()->with($templateLoader);
        $engine->shouldReceive('addFunction')->once()->with('assetUrl', $assetUrlFunction);
        $engine->shouldReceive('addFunction')->once()->with('csrfToken', $csrfTokenFunction);
        $engine->shouldReceive('addFunction')->once()->with('pathForRoute', $pathForRouteFunction);
        $engine->shouldReceive('addFunction')->once()->with('translate', $translateFunction);

        $engine->shouldReceive('renderToString')->with('template', ['key' => 'value'])->andReturn('content');

        new TemplateEngine(
            $engine,
            'locale',
            $templateLoader,
            $assetUrlFunction,
            $csrfTokenFunction,
            $pathForRouteFunction,
            $translateFunction,
        );
    }
    private function createInstance(Engine $engine)
    {
        return new TemplateEngine(
            $engine,
            'de-DE',
            $this->createStub(TemplateLoader::class),
            $this->createStub(AssetUrlFunction::class),
            $this->createStub(CsrfTokenFunction::class),
            $this->createStub(PathForRouteFunction::class),
            $this->createStub(TranslateFunction::class),
        );
    }

    public function testRender()
    {
        $engine = $this->createMock(Engine::class);
        $engine->method('renderToString')->with('template', ['key' => 'value'])->willReturn('content');

        $templateEngine = $this->createInstance($engine);
        $errorReporting = error_reporting();
        $this->assertEquals('content', $templateEngine->render('template', ['key' => 'value']));
        $this->assertEquals($errorReporting, error_reporting());
    }

    public function testRenderThrowsOnWarning()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            trigger_error('Warning should be converted to exception', E_USER_NOTICE);
            return '';
        });

        $templateEngine = $this->createInstance($engine);
        error_reporting(E_ALL ^ E_USER_NOTICE); // Throw even if E_USER_NOTICE is suppressed
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Warning should be converted to exception');
        $templateEngine->render('template');
        $this->assertEquals(E_ALL ^ E_USER_NOTICE, error_reporting());
    }

    public function testRenderHonorsErrorSuppression()
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('renderToString')->willReturnCallback(function () {
            @trigger_error('This warning should be suppressed', E_USER_NOTICE);
            return '';
        });

        $templateEngine = $this->createInstance($engine);
        error_reporting(E_USER_NOTICE);
        $templateEngine->render('template');
        $this->assertEquals(E_USER_NOTICE, error_reporting());
    }
}
