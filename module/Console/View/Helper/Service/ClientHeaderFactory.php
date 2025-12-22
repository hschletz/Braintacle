<?php

namespace Console\View\Helper\Service;

use Braintacle\Legacy\ApplicationService;
use Braintacle\Template\TemplateEngine;
use Console\View\Helper\ClientHeader;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for ClientHeader helper.
 */
class ClientHeaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var ApplicationService */
        $application = $container->get('Application');

        return new ClientHeader(
            $container->get(TemplateEngine::class),
            $application->getMvcEvent()->getRouteMatch()->getParam('action')
        );
    }
}
