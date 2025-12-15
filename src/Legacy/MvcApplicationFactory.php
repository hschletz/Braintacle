<?php

namespace Braintacle\Legacy;

use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Renderer\PhpRenderer;

/** @codeCoverageIgnore */
final class MvcApplicationFactory
{
    public function __invoke(ServiceManager $serviceManager)
    {
        // Resolve dependencies via the fully configured service manager. Do not
        // try to autowire the MvcApplication - the service manager knows the
        // Laminas\Mvc\Application instance only under the name 'Application',
        // and autowiring would create a new unconfigured instance.
        return new MvcApplication(
            $serviceManager->get('Application'),
            $serviceManager->get(ControllerManager::class),
            $serviceManager->get(PhpRenderer::class),
            $serviceManager->get(Translator::class),
        );
    }
}
