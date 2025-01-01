<?php

namespace Braintacle\Legacy;

use Braintacle\AppConfig;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Closure;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Http\Response;
use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Translator\TranslatorInterface;
use Laminas\View\Model\ViewModel;
use Model\Client\Client;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;

class MvcApplication
{
    private ?Closure $previousErrorHandler;
    private ServerRequestInterface $request;

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
        $serviceManager->setService(I18nTranslatorInterface::class, $this->container->get(I18nTranslator::class));
        $serviceManager->setService(LoggerInterface::class, $this->container->get(LoggerInterface::class));
        $serviceManager->setService(PathForRouteFunction::class, $this->container->get(PathForRouteFunction::class));
        $serviceManager->setService(TranslatorInterface::class, $this->container->get(TranslatorInterface::class));
    }

    public function configureEvents(): void
    {
        // Prevent the MVC application from applying a layout.
        $eventManager = $this->application->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, function (MvcEvent $event) {
            $result = $event->getResult();
            if ($result instanceof ViewModel) {
                $result->setTerminal(true);
                $event->setViewModel($result);
            }
        }, -95);

        // Prevent the MVC application from applying an error template.
        $eventManager->attach(MvcEvent::EVENT_DISPATCH, function (MvcEvent $event) {
            /** @var Response */
            $response = $event->getResponse();
            if ($response->getStatusCode() == 404) {
                // The controller did not provide the requested action.
                throw new HttpNotFoundException($this->request, 'Invalid action');
            }
        }, -85);
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $this->preventErrorPage(...), -10);
        $eventManager->attach(MvcEvent::EVENT_RENDER_ERROR, $this->preventErrorPage(...), -10);

        // Prevent the MVC application from generating output.
        $eventManager->attach(MvcEvent::EVENT_FINISH, function (MvcEvent $event) {
            $event->stopPropagation();
        });
    }

    public function run(ServerRequestInterface $request): MvcEvent
    {
        $this->request = $request;
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
     * Event handler to prevent Laminas framework from setting an error template.
     */
    public function preventErrorPage(MvcEvent $event)
    {
        $exception = $event->getParam('exception');
        if ($exception) {
            throw $exception;
        }
        switch ($event->getError()) {
            case Application::ERROR_CONTROLLER_NOT_FOUND:
                throw new HttpNotFoundException($this->request, 'Invalid controller name: ' . $event->getController());
            case Application::ERROR_ROUTER_NO_MATCH:
                throw new HttpNotFoundException($this->request, 'No route matched.');
            default:
                throw new RuntimeException('Unknown error in MVC application.');
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
