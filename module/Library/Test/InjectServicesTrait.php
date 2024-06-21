<?php

namespace Library\Test;

use Braintacle\AppConfig;
use Braintacle\Http\RouteHelper;
use Braintacle\I18n\Translator;
use Braintacle\Template\Function\AssetUrlFunction;
use Composer\InstalledVersions;
use Laminas\Config\Reader\ReaderInterface;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\ServiceManager\ServiceManager;

/**
 * Inject services which are normally injected during MVC bootstrapping.
 */
trait InjectServicesTrait
{
    /**
     * Call this on any newly created ServiceManager instance.
     */
    private static function injectServices(ServiceManager $serviceManager): void
    {
        // Inject empty dummy config. Tests that evaluate config set up their
        // own.
        $appConfig = new AppConfig(
            new class implements ReaderInterface
            {
                public function fromFile($filename)
                {
                    return [];
                }

                public function fromString($string)
                {
                    return [];
                }
            },
            ''
        );
        $serviceManager->setService(AppConfig::class, $appConfig);

        $routeHelper = new RouteHelper();
        $routeHelper->setBasePath('/assets');
        $serviceManager->setService(AssetUrlFunction::class, new AssetUrlFunction($routeHelper));

        // Create fully functional translator.
        $serviceManager->setService(TranslatorInterface::class, new Translator(
            'de',
            InstalledVersions::getRootPackage()['install_path'] . '/i18n',
            $appConfig
        ));
    }
}
