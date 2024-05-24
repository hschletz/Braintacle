<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Test\HttpHandlerTestTrait;
use Laminas\EventManager\EventManager;
use Laminas\Http\Response as MvcResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ApplicationBridgeTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $serviceManager = new ServiceManager();
        $eventManager = new EventManager();

        $application = $this->createStub(Application::class);
        $application->method('getServiceManager')->willReturn($serviceManager);
        $application->method('getEventManager')->willReturn($eventManager);
        $application->method('run')->willReturnCallback(function () use ($application, $eventManager) {
            $mvcResponse = new MvcResponse();
            $mvcResponse->setStatusCode(418);
            $mvcResponse->getHeaders()->addHeaders([
                'X-Header1: header1',
                'X-Header2: header2a',
                'X-Header2: header2b',
            ]);
            $mvcResponse->setContent('content');

            $event = new MvcEvent(MvcEvent::EVENT_FINISH);
            $event->setResponse($mvcResponse);

            $eventManager->triggerEvent($event);

            return $application;
        });

        $applicationBridge = new ApplicationBridge($this->response, $appConfig, $application);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseStatusCode(418, $response);
        $this->assertResponseHeaders([
            'X-Header1' => ['header1'],
            'X-Header2' => ['header2a', 'header2b'],
        ], $response);
        $this->assertResponseContent('content', $response);

        $this->assertTrue($serviceManager->has(AppConfig::class));
        $this->assertSame($appConfig, $serviceManager->get(AppConfig::class));
    }
}
