<?php

namespace Console\Template;

use Console\Template\Extensions\AssetLoaderExtension;
use Console\Template\Filters\DateFormatFilter;
use Console\Template\Filters\NumberFormatFilter;
use Console\Template\Functions\ConsoleUrlFunction;
use Console\Template\Functions\TranslateFunction;
use Console\View\Helper\ConsoleScript;
use Console\View\Helper\ConsoleUrl;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\HelperPluginManager;
use Latte\Engine;
use Psr\Container\ContainerInterface;

class TemplateRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $translator = $container->get(Translator::class);

        /** @var HelperPluginManager */
        $viewHelperManager = $container->get('ViewHelperManager');
        $consoleScript = $viewHelperManager->get(ConsoleScript::class);
        $consoleUrl = $viewHelperManager->get(ConsoleUrl::class);

        $consoleUrlFunction = new ConsoleUrlFunction($consoleUrl);

        // Use custom function instead of TranslatorExtension to make strings
        // easier to extract.
        $translateFunction = new TranslateFunction($translator);

        $dateFormatFilter = $container->get(DateFormatFilter::class);
        $numberFormatFilter = new NumberFormatFilter();

        $assetLoaderExtension = new AssetLoaderExtension($consoleScript);

        $engine = new Engine();
        $engine->addFunction('consoleUrl', $consoleUrlFunction);
        $engine->addFunction('translate', $translateFunction);

        $engine->addFilter('dateFormat', $dateFormatFilter);
        $engine->addFilter('numberFormat', $numberFormatFilter);

        $engine->addExtension($assetLoaderExtension);

        return new TemplateRenderer($engine);
    }
}
