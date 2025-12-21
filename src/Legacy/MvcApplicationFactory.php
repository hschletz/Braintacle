<?php

namespace Braintacle\Legacy;

use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Renderer\PhpRenderer;

/** @codeCoverageIgnore */
final class MvcApplicationFactory
{
    public function __invoke(ServiceManager $serviceManager)
    {
        // Resolve dependencies via the service manager, not the standard
        // container. Do not try to autowire the MvcApplication - autowiring is
        // not set up yet when this factory is invoked.
        return new MvcApplication(
            $serviceManager->get('Application'),
            $serviceManager->get(PluginManager::class),
            $serviceManager->get(PhpRenderer::class),
            $serviceManager->get(TranslatorInterface::class),
        );
    }
}
