<?php

namespace Library\Test;

use Braintacle\AppConfig;
use Braintacle\I18n\Translator;
use Braintacle\Legacy\I18nTranslator;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\Function\TranslateFunction;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Composer\InstalledVersions;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Latte\Engine;
use Mockery;
use Mockery\Mock;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Inject services which are normally injected during MVC bootstrapping.
 *
 * Some services are stubs created using Mockery, because PHPUnit's mocks/stubs
 * cannot be created from a static method. Unlike regular test classes using
 * Mockery, the MockeryPHPUnitIntegration trait MUST NOT be used in this trait!
 * This would lead to infinite recursion when used with classes that use
 * MockeryPHPUnitIntegration too. That's OK because the created objects are
 * simple stubs, and no assertions are needed.
 *
 * @psalm-require-extends \PHPUnit\Framework\TestCase
 */
trait InjectServicesTrait
{
    /**
     * Call this on any newly created ServiceManager instance.
     */
    private static function injectServices(ServiceManager $serviceManager): void
    {
        $rootPath = InstalledVersions::getRootPackage()['install_path'];

        // Inject empty dummy config. Tests that evaluate config set up their
        // own.
        /** @var Mock|Filesystem */
        $filesystem = Mockery::mock(Filesystem::class);
        $filesystem->shouldReceive('readFile')->andReturn('');
        $appConfig = new AppConfig($filesystem, '');
        $serviceManager->setService(AppConfig::class, $appConfig);

        /** @var Mock|AssetUrlFunction */
        $assetUrlFunction = Mockery::mock(AssetUrlFunction::class);
        $assetUrlFunction->shouldReceive('__invoke')->andReturnUsing(
            fn(string $path) => "/assets/$path"
        );
        $serviceManager->setService(AssetUrlFunction::class, $assetUrlFunction);

        /** @var Mock|CsrfTokenFunction */
        $csrfTokenFunction = Mockery::mock(CsrfTokenFunction::class);

        /** @var Mock|PathForRouteFunction */
        $pathForRouteFunction = Mockery::mock(PathForRouteFunction::class);
        $pathForRouteFunction->shouldReceive('__invoke')->andReturnUsing(
            fn(string $name) => "/route/$name"
        );
        $serviceManager->setService(PathForRouteFunction::class, $pathForRouteFunction);

        // Create fully functional translator.
        $translator = new I18nTranslator(
            new Translator(
                'de',
                $rootPath . '/i18n',
                $appConfig,
            )
        );
        $serviceManager->setService(TranslatorInterface::class, $translator);

        $templateEngine = new TemplateEngine(
            new Engine(),
            'de-DE',
            new TemplateLoader($rootPath . '/templates'),
            $assetUrlFunction,
            $csrfTokenFunction,
            $pathForRouteFunction,
            new TranslateFunction($translator),
        );
        $serviceManager->setService(TemplateEngine::class, $templateEngine);
    }
}
