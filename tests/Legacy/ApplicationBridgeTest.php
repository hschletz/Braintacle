<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
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
use Laminas\View\Model\ViewModel;
use Model\Client\Client;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ApplicationBridgeTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $services = [
            AbstractDatabase::class => $this->createStub(AbstractDatabase::class),
            Adapter::class => $this->createStub(Adapter::class),
            AppConfig::class => $this->createStub(AppConfig::class),
            AssetUrlFunction::class => $this->createStub(AssetUrlFunction::class),
            Client::class => $this->createStub(Client::class),
            ClockInterface::class => $this->createStub(ClockInterface::class),
            Group::class => $this->createStub(Group::class),
            PathForRouteFunction::class => $this->createStub(PathForRouteFunction::class),
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
            $mvcResponse->setContent('mvc_content');

            $event = new MvcEvent(MvcEvent::EVENT_FINISH);
            $event->setResponse($mvcResponse);
            $event->setParam('template', 'layout_template');
            $event->setParam('subMenuRoute', 'sub_menu_route');

            $eventManager->triggerEvent($event);

            return $application;
        });

        $templateEngine = $this->createMock(TemplateEngine::class);
        $templateEngine->method('render')->with(
            'layout_template',
            ['content' => 'mvc_content', 'subMenuRoute' => 'sub_menu_route'],
        )->willReturn('layout_content');

        $applicationBridge = new ApplicationBridge($this->response, $container, $application, $templateEngine);
        $response = $applicationBridge->handle($this->request);

        $this->assertResponseStatusCode(418, $response);
        $this->assertResponseHeaders([
            'X-Header1' => ['header1'],
            'X-Header2' => ['header2a', 'header2b'],
        ], $response);
        $this->assertResponseContent('layout_content', $response);

        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get(AbstractDatabase::class));
        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get('Database\Nada'));
        $this->assertSame($services[Adapter::class], $serviceManager->get(Adapter::class));
        $this->assertSame($services[Adapter::class], $serviceManager->get('Db'));
        $this->assertSame($services[AppConfig::class], $serviceManager->get(AppConfig::class));
        $this->assertSame($services[AssetUrlFunction::class], $serviceManager->get(AssetUrlFunction::class));
        $this->assertSame($services[Client::class], $serviceManager->get(Client::class));
        $this->assertSame($services[ClockInterface::class], $serviceManager->get(ClockInterface::class));
        $this->assertSame($services[Group::class], $serviceManager->get(Group::class));
        $this->assertSame($services[PathForRouteFunction::class], $serviceManager->get(PathForRouteFunction::class));
        $this->assertSame($services[TranslatorInterface::class], $serviceManager->get(TranslatorInterface::class));
        $this->assertSame($serviceManager, $serviceManager->get(ContainerInterface::class));

        $this->assertSame($serviceManager, $container->get(ServiceLocatorInterface::class));
        $this->assertSame($serviceManager, $container->get(ServiceManager::class));
        $this->assertSame($services[AbstractDatabase::class], $container->get('Database\Nada'));
        $this->assertSame($services[Adapter::class], $container->get('Db'));
    }

    public function testPreventMvcLayoutWithoutViewModel()
    {
        $viewModel = new ViewModel();
        $viewModel->setTerminal(false);

        $event = new MvcEvent();
        $event->setResult('result');
        $event->setViewModel($viewModel);

        $applicationBridge = new ApplicationBridge(
            $this->createStub(ResponseInterface::class),
            $this->createStub(Container::class),
            $this->createStub(Application::class),
            $this->createStub(TemplateEngine::class),
        );
        $applicationBridge->preventMvcLayout($event);

        $this->assertEquals('result', $event->getResult());
        $this->assertSame($viewModel, $event->getViewModel());
        $this->assertFalse($viewModel->terminate());
    }

    public function testPreventMvcLayoutWithViewModel()
    {
        $result = new ViewModel();
        $result->setTerminal(false);

        $event = new MvcEvent();
        $event->setResult($result);
        $event->setViewModel(new ViewModel());

        $applicationBridge = new ApplicationBridge(
            $this->createStub(ResponseInterface::class),
            $this->createStub(Container::class),
            $this->createStub(Application::class),
            $this->createStub(TemplateEngine::class),
        );
        $applicationBridge->preventMvcLayout($event);

        $this->assertSame($result, $event->getResult());
        $this->assertSame($result, $event->getViewModel());
        $this->assertTrue($result->terminate());
    }
}
