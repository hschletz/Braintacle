<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Test\HttpHandlerTestTrait;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\EventManager\EventManager;
use Laminas\Http\Response as MvcResponse;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Model\Client\Client;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;

class ApplicationBridgeTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $services = [
            AbstractDatabase::class => $this->createStub(AbstractDatabase::class),
            Adapter::class => $this->createStub(Adapter::class),
            AppConfig::class => $this->createStub(AppConfig::class),
            Client::class => $this->createStub(Client::class),
            ClockInterface::class => $this->createStub(ClockInterface::class),
            Group::class => $this->createStub(Group::class),
            TranslatorInterface::class => $this->createStub(TranslatorInterface::class),
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
        $this->assertSame($services[Client::class], $serviceManager->get(Client::class));
        $this->assertSame($services[ClockInterface::class], $serviceManager->get(ClockInterface::class));
        $this->assertSame($services[Group::class], $serviceManager->get(Group::class));
        $this->assertSame($services[TranslatorInterface::class], $serviceManager->get(TranslatorInterface::class));
        $this->assertSame($serviceManager, $serviceManager->get(ContainerInterface::class));

        $this->assertSame($serviceManager, $container->get(ServiceLocatorInterface::class));
        $this->assertSame($serviceManager, $container->get(ServiceManager::class));
        $this->assertSame($services[AbstractDatabase::class], $container->get('Database\Nada'));
        $this->assertSame($services[Adapter::class], $container->get('Db'));
    }
}
