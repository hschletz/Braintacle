<?php

namespace Braintacle\Legacy;

use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\I18n\Translator;
use Laminas\Mvc\MvcEvent;
use Laminas\Validator\AbstractValidator;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Variables;
use LogicException;
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
        private Translator $translator,
    ) {}

    public function run(ServerRequestInterface $request): MvcEvent
    {
        // Application::run() may trigger a warning. This seems to be caused by
        // inconsistent Container interface usage throughout the Laminas code
        // and cannot be fixed here. Suppress the warning via a custom error
        // handler. suppression via @ would suppress all warnings from our own
        // code, too.
        $this->previousErrorHandler = set_error_handler($this->errorHandler(...), E_USER_DEPRECATED | E_USER_NOTICE);
        try {
            AbstractValidator::setDefaultTranslator($this->translator);

            $mvcEvent = $this->application->getMvcEvent();
            $mvcEvent->setParam(self::Psr7Request, $request);

            $this->route($mvcEvent);
            $this->dispatch($mvcEvent);
            $this->render($mvcEvent);
        } finally {
            restore_error_handler();
        }

        return $mvcEvent;
    }

    private function route(MvcEvent $mvcEvent): void
    {
        $router = $mvcEvent->getRouter();
        $routeMatch = $router->match($mvcEvent->getRequest());
        if (!$routeMatch) {
            throw new HttpNotFoundException($mvcEvent->getParam(self::Psr7Request), 'No route matched.');
        }
        $mvcEvent->setRouteMatch($routeMatch);
    }

    private function dispatch(MvcEvent $mvcEvent): void
    {
        $controllerName = $mvcEvent->getRouteMatch()->getParam('controller');

        if (! $this->controllerManager->has($controllerName)) {
            throw new HttpNotFoundException(
                $mvcEvent->getParam(self::Psr7Request),
                'Invalid controller name: ' . $controllerName,
            );
        }

        /** @var Controller */
        $controller = $this->controllerManager->get($controllerName);
        $controller->setEvent($mvcEvent);

        $result = $controller->dispatch($mvcEvent->getRequest());
        $mvcEvent->setResult($result);
    }

    private function render(MvcEvent $mvcEvent): void
    {
        $result = $mvcEvent->getResult();
        if (! $result instanceof Response) {
            if (is_array($result)) {
                $routeMatch = $mvcEvent->getRouteMatch();
                $controller = $routeMatch->getParam('controller');
                $action = $routeMatch->getParam('action');

                $result = new ViewModel($result);
                $result->setTemplate("console/$controller/$action");
            }
            assert($result instanceof ViewModel);
            $variables = $result->getVariables();
            if (! $variables instanceof Variables) {
                assert(is_array($variables));
                $variables = new Variables($variables);
                $result->setVariables($variables, true);
            }
            $variables->setStrictVars(true);

            $mvcEvent->getResponse()->setContent($this->phpRenderer->render($result));
        }
    }

    public function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        if (
            $errno == E_USER_DEPRECATED && str_starts_with(
                $errstr,
                'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a '
            )
        ) {
            return true;
        } elseif ($errno == E_USER_NOTICE && preg_match('/View variable ".+" does not exist/', $errstr)) {
            throw new LogicException($errstr);
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
