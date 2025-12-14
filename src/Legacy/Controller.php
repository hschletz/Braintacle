<?php

namespace Braintacle\Legacy;

use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Override;

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
}
