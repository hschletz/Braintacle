<?php

namespace Braintacle\Legacy;

use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use LogicException;
use Override;

/**
 * Stub for the 'Application' service.
 */
final class ApplicationService implements ApplicationInterface
{
    private MvcEvent $mvcEvent;

    public function __construct(
        private ServiceManager $serviceManager,
        private Request $request,
        private Response $response,
        RouteStackInterface $router,
    ) {
        $this->mvcEvent = new MvcEvent();
        $this->mvcEvent->setName(MvcEvent::EVENT_BOOTSTRAP);
        $this->mvcEvent->setTarget($this);
        $this->mvcEvent->setApplication($this);
        $this->mvcEvent->setRequest($request);
        $this->mvcEvent->setResponse($response);
        $this->mvcEvent->setRouter($router);
    }

    public function getMvcEvent(): MvcEvent
    {
        return $this->mvcEvent;
    }

    #[Override]
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    #[Override]
    public function getRequest()
    {
        return $this->request;
    }

    #[Override]
    public function getResponse()
    {
        return $this->response;
    }

    #[Override]
    public function getEventManager()
    {
        throw new LogicException('This stub implementation does not have an event manager.');
    }

    #[Override]
    public function run()
    {
        throw new LogicException('This stub implementation can not be run.');
    }
}
