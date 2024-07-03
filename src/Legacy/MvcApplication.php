<?php

namespace Braintacle\Legacy;

use Braintacle\AppConfig;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Closure;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\ViewModel;
use Model\Client\Client;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

class MvcApplication
{
    private ?Closure $previousErrorHandler;

    public function __construct(
        private Application $application,
        private Container $container,
    ) {
    }

    public function configureServices(): void
    {
        $serviceManager = $this->application->getServiceManager();

        // Inject Services which are not provided by module's ServiceManager
        // configuration.
        $serviceManager->setService(AbstractDatabase::class, $this->container->get(AbstractDatabase::class));
        $serviceManager->setService(Adapter::class, $this->container->get(Adapter::class));
        $serviceManager->setService(AppConfig::class, $this->container->get(AppConfig::class));
        $serviceManager->setService(AssetUrlFunction::class, $this->container->get(AssetUrlFunction::class));
        $serviceManager->setService(Client::class, $this->container->get(Client::class));
        $serviceManager->setService(ClockInterface::class, $this->container->get(ClockInterface::class));
        $serviceManager->setService(ContainerInterface::class, $serviceManager);
        $serviceManager->setService(Group::class, $this->container->get(Group::class));
        $serviceManager->setService(PathForRouteFunction::class, $this->container->get(PathForRouteFunction::class));
        $serviceManager->setService(TranslatorInterface::class, $this->container->get(TranslatorInterface::class));

        // Create legacy service definitions in main container, allowing
        // autowiring classes that still depend on these services.
        $this->container->set(ServiceLocatorInterface::class, $serviceManager);
        $this->container->set(ServiceManager::class, $serviceManager);
    }

    public function configureEvents(): void
    {
        // Prevent the MVC application from applying a layout.
        $eventManager = $this->application->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, $this->preventMvcLayout(...), -95);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $this->preventMvcLayout(...), -95);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, $this->preventMvcLayout(...), -95);

        // Prevent the MVC application from generating output.
        $eventManager->attach(MvcEvent::EVENT_FINISH, function (MvcEvent $event) {
            $event->stopPropagation();
        });
    }

    public function run(): MvcEvent
    {
        $this->configureServices();
        $this->configureEvents();

        // Application::run() may trigger a warning. This seems to be caused by
        // inconsistent Container interface usage throughout the Laminas code
        // and cannot be fixed here. Suppress the warning via a custom error
        // handler. suppression via @ would suppress all warnings from our own
        // code, too.
        $this->previousErrorHandler = set_error_handler($this->errorHandler(...), E_USER_DEPRECATED);
        try {
            $this->application->run();
        } finally {
            restore_error_handler();
        }

        return $this->application->getMvcEvent();
    }

    /**
     * Event handler to prevent Laminas framework from applying a layout.
     */
    public function preventMvcLayout(MvcEvent $event): void
    {
        $result = $event->getResult();
        if ($result instanceof ViewModel) {
            $result->setTerminal(true);
            $event->setViewModel($result);
        }
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (
            str_starts_with(
                $errstr,
                'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a '
            )
        ) {
            return true;
        } elseif ($this->previousErrorHandler) {
            return ($this->previousErrorHandler)($errno, $errstr, $errfile, $errline);
        } else {
            // This branch is unreachable in tests
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
    }
}
