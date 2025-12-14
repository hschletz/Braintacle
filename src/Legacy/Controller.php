<?php

namespace Braintacle\Legacy;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Override;
use Slim\Exception\HttpNotFoundException;

/**
 * Base class for MVC controllers.
 */
abstract class Controller extends AbstractActionController
{
    #[Override]
    public function dispatch(RequestInterface $request, ?ResponseInterface $response = null)
    {
        // Reimplementation that does not dispatch events.

        $this->request = $request;
        $this->response = new Response();

        $event = $this->getEvent();
        $event->setName(MvcEvent::EVENT_DISPATCH);
        $event->setRequest($request);
        $event->setResponse($this->response);
        $event->setTarget($this);

        return $this->onDispatch($event);
    }

    #[Override]
    public function onDispatch(MvcEvent $e)
    {
        $action = $e->getRouteMatch()->getParam('action');
        $method = $action . 'Action';
        if (!method_exists($this, $method)) {
            throw new HttpNotFoundException($e->getParam(MvcApplication::Psr7Request), 'Invalid action');
        }

        $result = $this->$method();
        $e->setResult($result);

        return $result;
    }
}
