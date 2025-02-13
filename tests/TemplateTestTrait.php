<?php

namespace Braintacle\Test;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\Function\TranslateFunction;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Composer\InstalledVersions;
use Latte\Engine;

/**
 * Utility trait for testing templates.
 */
trait TemplateTestTrait
{
    /**
     * Create fully functional template engine with stubs for template
     * functions.
     *
     * If the default stubs defined in this trait are not suitable, they can be
     * overridden individually.
     */
    private function createTemplateEngine(array $templateFunctions = []): TemplateEngine
    {
        return new TemplateEngine(
            new Engine(),
            'de-DE',
            new TemplateLoader(InstalledVersions::getRootPackage()['install_path'] . 'templates'),
            $templateFunctions[AssetUrlFunction::class] ?? $this->createStub(AssetUrlFunction::class),
            $templateFunctions[CsrfTokenFunction::class] ?? $this->createCsrfTokenFunctionStub(),
            $templateFunctions[PathForRouteFunction::class] ?? $this->createPathForRouteFunctionStub(),
            $templateFunctions[TranslateFunction::class] ?? $this->createTranslateFunctionStub(),
        );
    }

    private function createCsrfTokenFunctionStub(): CsrfTokenFunction
    {
        $function = $this->createStub(CsrfTokenFunction::class);
        $function->method('__invoke')->willReturn('csrf_token');

        return $function;
    }

    private function createPathForRouteFunctionStub(): PathForRouteFunction
    {
        $function = $this->createStub(PathForRouteFunction::class);
        $function->method('__invoke')->willReturnCallback(
            function (string $name, array $routeArguments, array $queryParams): string {
                return $name . '/' . http_build_query($routeArguments) . '?' . http_build_query($queryParams);
            }
        );

        return $function;
    }

    private function createTranslateFunctionStub(): TranslateFunction
    {
        $function = $this->createStub(TranslateFunction::class);
        $function->method('__invoke')->willReturnCallback(fn($message, ...$args) => '_' . vsprintf($message, $args));

        return $function;
    }
}
