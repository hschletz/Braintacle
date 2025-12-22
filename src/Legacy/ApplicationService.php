<?php

namespace Braintacle\Legacy;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use LogicException;

/**
 * Stub for the 'Application' service.
 */
final class ApplicationService
{
    private MvcEvent $mvcEvent;

    public function __construct(
        private ServiceManager $serviceManager,
        private Request $request,
        private Response $response,
        RouteStackInterface $router,
    ) {
        $this->mvcEvent = new MvcEvent();
        $this->mvcEvent->setRequest($request);
        $this->mvcEvent->setResponse($response);
        $this->mvcEvent->setRouter($router);
    }

    public function getMvcEvent(): MvcEvent
    {
        return $this->mvcEvent;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getEventManager()
    {
        throw new LogicException('This stub implementation does not have an event manager.');
    }

    public function run()
    {
        throw new LogicException('This stub implementation can not be run.');
    }
}
