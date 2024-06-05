<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Test\HttpHandlerTestTrait;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\EventManager\EventManager;
use Laminas\Http\Response as MvcResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use Nada\Database\AbstractDatabase;
use PHPUnit\Framework\TestCase;

class ApplicationBridgeTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $services = [
            AbstractDatabase::class => $this->createStub(AbstractDatabase::class),
            Adapter::class => $this->createStub(Adapter::class),
            AppConfig::class => $this->createStub(AppConfig::class),
        ];
        $container = new Container($services);

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

        $applicationBridge = new ApplicationBridge($this->response, $container, $application);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseStatusCode(418, $response);
        $this->assertResponseHeaders([
            'X-Header1' => ['header1'],
            'X-Header2' => ['header2a', 'header2b'],
        ], $response);
        $this->assertResponseContent('content', $response);

        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get(AbstractDatabase::class));
        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get('Database\Nada'));
        $this->assertSame($services[Adapter::class], $serviceManager->get(Adapter::class));
        $this->assertSame($services[Adapter::class], $serviceManager->get('Db'));
        $this->assertSame($services[AppConfig::class], $serviceManager->get(AppConfig::class));
    }
}
