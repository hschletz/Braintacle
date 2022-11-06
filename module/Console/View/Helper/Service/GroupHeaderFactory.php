<?php

namespace Console\View\Helper\Service;

use Console\Template\TemplateRenderer;
use Console\View\Helper\GroupHeader;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for GroupHeader
 */
class GroupHeaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var Application */
        $application = $container->get('Application');

        return new GroupHeader(
            $container->get(TemplateRenderer::class),
            $application->getMvcEvent()->getRouteMatch()->getParam('action')
        );
    }
}
