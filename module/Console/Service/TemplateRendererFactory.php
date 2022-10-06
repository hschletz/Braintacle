<?php

namespace Console\Service;

use Console\Template\TemplateRenderer;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Latte\Engine;
use Psr\Container\ContainerInterface;

class TemplateRendererFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $engine = new Engine();
        $translator = $container->get(Translator::class);

        return new TemplateRenderer($engine, $translator);
    }
}
