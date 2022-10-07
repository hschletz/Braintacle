<?php

namespace Console\View\Helper\Service;

use Console\Template\TemplateRenderer;
use Console\View\Helper\GroupHeader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\View\Helper\Navigation;
use Psr\Container\ContainerInterface;

/**
 * Factory for GroupHeader
 */
class GroupHeaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $viewHelperManager = $container->get('ViewHelperManager');
        return new GroupHeader(
            $viewHelperManager->get(Navigation::class),
            $container->get(TemplateRenderer::class)
        );
    }
}
