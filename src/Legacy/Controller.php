<?php

namespace Braintacle\Legacy;

use Braintacle\Legacy\Plugin\PluginManager;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Http\Request;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Override;
use Slim\Exception\HttpNotFoundException;

/**
 * Base class for MVC controllers.
 */
abstract class Controller implements DispatchableInterface
{
    private MvcEvent $mvcEvent;
    private PluginManager $pluginManager;
    private Request $request;

    public function __call($name, $arguments)
    {
        $plugin = $this->pluginManager->get($name);
        return $plugin(...$arguments);
    }

    public function getEvent(): MvcEvent
    {
        return $this->mvcEvent;
    }

    public function setEvent(MvcEvent $event): void
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
