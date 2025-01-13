<?php

namespace Console\Template;

use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\CsrfTokenFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Console\Template\Filters\DateFormatFilter;
use Console\Template\Functions\TranslateFunction;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Latte\Engine;
use Psr\Container\ContainerInterface;

class TemplateRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $translator = $container->get(Translator::class);

        // Use custom function instead of TranslatorExtension to make strings
        // easier to extract.
        $translateFunction = new TranslateFunction($translator);

        $dateFormatFilter = $container->get(DateFormatFilter::class);

        $engine = new Engine();
        $engine->addFunction('assetUrl', $container->get(AssetUrlFunction::class));
        $engine->addFunction('csrfToken', $container->get(CsrfTokenFunction::class));
        $engine->addFunction('pathForRoute', $container->get(PathForRouteFunction::class));
        $engine->addFunction('translate', $translateFunction);

        $engine->addFilter('dateFormat', $dateFormatFilter);

        return new TemplateRenderer($engine);
    }
}
