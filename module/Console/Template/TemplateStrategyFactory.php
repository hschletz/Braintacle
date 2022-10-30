<?php

namespace Console\Template;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class TemplateStrategyFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $templateRenderer = $container->get(TemplateRenderer::class);
        return new TemplateStrategy($templateRenderer);
    }
}
