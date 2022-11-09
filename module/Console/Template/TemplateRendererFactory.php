<?php

namespace Console\Template;

use Console\Template\Filters\DateFormatFilter;
use Console\Template\Filters\NumberFormatFilter;
use Console\Template\Functions\ConsoleUrlFunction;
use Console\Template\Functions\TranslateFunction;
use Console\View\Helper\ConsoleUrl;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Latte\Engine;
use Psr\Container\ContainerInterface;

class TemplateRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $translator = $container->get(Translator::class);
        $consoleUrl = $container->get('ViewHelperManager')->get(ConsoleUrl::class);

        $translateFunction = new TranslateFunction($translator);
        $consoleUrlFunction = new ConsoleUrlFunction($consoleUrl);
        $numberFormatFilter = new NumberFormatFilter();
        $dateFormatFilter = new DateFormatFilter();

        $engine = new Engine();
        $engine->addFunction('translate', $translateFunction);
        $engine->addFunction('consoleUrl', $consoleUrlFunction);
        $engine->addFilter('numberFormat', $numberFormatFilter);
        $engine->addFilter('dateFormat', $dateFormatFilter);

        return new TemplateRenderer($engine);
    }
}
