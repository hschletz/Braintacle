<?php

namespace Braintacle\Legacy;

use Laminas\EventManager\EventInterface;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Override;
use Slim\Exception\HttpNotFoundException;

/**
 * Base class for MVC controllers.
 */
abstract class Controller implements DispatchableInterface, InjectApplicationEventInterface
{
    private MvcEvent $mvcEvent;
    private PluginManager $pluginManager;
    private Request $request;

    public function __call($name, $arguments)
    {
        $plugin = $this->pluginManager->get($name);
        if (is_callable($plugin)) {
            /** @psalm-suppress InvalidFunctionCall */
            return $plugin(...$arguments);
        } else {
            return $plugin;
        }
    }

    #[Override]
    public function getEvent(): MvcEvent
    {
        return $this->mvcEvent;
    }

    #[Override]
    public function setEvent(EventInterface $event): void
    {
        $this->mvcEvent = $event;
    }

    public function setPluginManager(PluginManager $pluginManager): void
    {
        $this->pluginManager = $pluginManager;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->mvcEvent->getResponse();
    }

    #[Override]
    public function dispatch(RequestInterface $request, ?ResponseInterface $response = null)
    {
        $this->request = $request;

        $event = $this->getEvent();
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $event->setRequest($request);

        return $this->onDispatch($event);
    }

    public function onDispatch(MvcEvent $e)
    {
        $action = $e->getRouteMatch()->getParam('action');
        $method = $this->getMethodFromAction($action);
        if (!method_exists($this, $method)) {
            throw new HttpNotFoundException($e->getParam(MvcApplication::Psr7Request), 'Invalid action');
        }

        $result = $this->$method();
        $e->setResult($result);

        return $result;
    }

    public function getMethodFromAction(string $action): string
    {
        return $action . 'Action';
    }
}
