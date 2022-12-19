<?php

namespace Console\Template;

use Console\Template\Filters\DateFormatFilter;
use Console\Template\Filters\NumberFormatFilter;
use Console\Template\Functions\AssetLoaderFunctions;
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

        $assetLoaderFunctions = new AssetLoaderFunctions($consoleScript);
        $consoleUrlFunction = new ConsoleUrlFunction($consoleUrl);
        $translateFunction = new TranslateFunction($translator);

        $dateFormatFilter = new DateFormatFilter();
        $numberFormatFilter = new NumberFormatFilter();

        $engine = new Engine();
        $engine->addFunction('addScript', [$assetLoaderFunctions, 'addScript']);
        $engine->addFunction('consoleUrl', $consoleUrlFunction);
        $engine->addFunction('translate', $translateFunction);

        $engine->addFilter('dateFormat', $dateFormatFilter);
        $engine->addFilter('numberFormat', $numberFormatFilter);

        return new TemplateRenderer($engine);
    }
}
