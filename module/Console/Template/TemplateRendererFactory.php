<?php

namespace Console\Template;

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

        $engine = new Engine();
        $engine->addFunction('translate', $translateFunction);
        $engine->addFunction('consoleUrl', $consoleUrlFunction);

        return new TemplateRenderer($engine);
    }
}
