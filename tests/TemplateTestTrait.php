<?php

namespace Braintacle\Test;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use Composer\InstalledVersions;
use Console\Template\Functions\TranslateFunction;
use Console\Template\TemplateLoader;
use Latte\Engine;

/**
 * Utility trait for testing templates.
 */
trait TemplateTestTrait
{
    private function createTemplateEngine(array $templateFunctions = []): TemplateEngine
    {
        return new TemplateEngine(
            new Engine(),
            new TemplateLoader(InstalledVersions::getRootPackage()['install_path'] . 'templates'),
            $templateFunctions[AssetUrlFunction::class] ?? $this->createStub(AssetUrlFunction::class),
            $templateFunctions[CsrfTokenFunction::class] ?? $this->createStub(CsrfTokenFunction::class),
            $templateFunctions[PathForRouteFunction::class] ?? $this->createStub(PathForRouteFunction::class),
            $templateFunctions[TranslateFunction::class] ?? $this->createStub(TranslateFunction::class),
        );
    }
}
