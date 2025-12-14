<?php

namespace Braintacle\Legacy;

use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

class MvcApplication
{
    public const Psr7Request = 'Psr7Request';

    private mixed $previousErrorHandler; // callable, but that cannot be used as property type

    public function __construct(
        private Application $application,
        private ControllerManager $controllerManager,
        private PhpRenderer $phpRenderer,
    ) {}

    public function run(ServerRequestInterface $request): MvcEvent
    {
        // Application::run() may trigger a warning. This seems to be caused by
        // inconsistent Container interface usage throughout the Laminas code
        // and cannot be fixed here. Suppress the warning via a custom error
        // handler. suppression via @ would suppress all warnings from our own
        // code, too.
        $this->previousErrorHandler = set_error_handler($this->errorHandler(...), E_USER_DEPRECATED);
        try {
            $mvcEvent = $this->application->getMvcEvent();
            $mvcEvent->setParam(self::Psr7Request, $request);

            $router = $mvcEvent->getRouter();
            $routeMatch = $router->match($mvcEvent->getRequest());
            if (!$routeMatch) {
                throw new HttpNotFoundException($request, 'No route matched.');
            }
            $mvcEvent->setRouteMatch($routeMatch);
            $controllerName = $routeMatch->getParam('controller');

            if (! $this->controllerManager->has($controllerName)) {
                throw new HttpNotFoundException($request, 'Invalid controller name: ' . $controllerName);
            }
            /** @var Controller */
            $controller = $this->controllerManager->get($controllerName);
            $controller->setEvent($mvcEvent);

            $result = $controller->dispatch($mvcEvent->getRequest());

            if (! $result instanceof Response) {
                if (is_array($result)) {
                    $action = $routeMatch->getParam('action');
                    $template = "console/$controllerName/$action";
                    $mvcEvent->getResponse()->setContent($this->phpRenderer->render($template, $result));
                } else {
                    assert($result instanceof ViewModel);
                    $mvcEvent->getResponse()->setContent($this->phpRenderer->render($result));
                }
            }
        } finally {
            restore_error_handler();
        }

        return $mvcEvent;
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

    public function getMvcEvent(): MvcEvent
    {
        return $this->application->getMvcEvent();
    }
}
