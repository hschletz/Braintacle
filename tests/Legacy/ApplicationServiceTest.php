<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\ApplicationService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplicationService::class)]
final class ApplicationServiceTest extends TestCase
{
    private function createApplicationService(
        ?ServiceManager $serviceManager = null,
        ?Request $request = null,
        ?Response $response = null,
        ?RouteStackInterface $router = null,
    ): ApplicationService {
        return new ApplicationService(
            $serviceManager ?? $this->createStub(ServiceManager::class),
            $request ?? $this->createStub(Request::class),
            $response ?? $this->createStub(Response::class),
            $router ?? $this->createStub(RouteStackInterface::class),
        );
    }

    public function testMvcEvent()
    {
        $request = $this->createStub(Request::class);
        $response = $this->createStub(Response::class);
        $router = $this->createStub(RouteStackInterface::class);

        $applicationService = $this->createApplicationService(
            request: $request,
            response: $response,
            router: $router,
        );
        $mvcEvent = $applicationService->getMvcEvent();

        $this->assertSame($request, $mvcEvent->getRequest());
        $this->assertSame($response, $mvcEvent->getResponse());
        $this->assertSame($router, $mvcEvent->getRouter());
        $this->assertSame($applicationService, $mvcEvent->getApplication());
        $this->assertSame($applicationService, $mvcEvent->getTarget());
        $this->assertEquals(MvcEvent::EVENT_BOOTSTRAP, $mvcEvent->getName());
    }

    public function testGetServiceManager()
    {
        $serviceManager = $this->createStub(ServiceManager::class);

        $applicationService = $this->createApplicationService(serviceManager: $serviceManager);
        $this->assertSame($serviceManager, $applicationService->getServiceManager());
    }

    public function testGetRequest()
    {
        $request = $this->createStub(Request::class);

        $applicationService = $this->createApplicationService(request: $request);
        $this->assertSame($request, $applicationService->getRequest());
    }

    public function testGetResponse()
    {
        $response = $this->createStub(Response::class);

        $applicationService = $this->createApplicationService(response: $response);
        $this->assertSame($response, $applicationService->getResponse());
    }

    public function testGetEventManager()
    {
        $applicationService = $this->createApplicationService();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This stub implementation does not have an event manager.');
        $applicationService->getEventManager();
    }

    public function testRun()
    {
        $applicationService = $this->createApplicationService();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('This stub implementation can not be run.');
        $applicationService->run();
    }
}
