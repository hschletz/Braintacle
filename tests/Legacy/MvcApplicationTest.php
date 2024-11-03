<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Test\ErrorHandlerTestTrait;
use Closure;
use DI\Container;
use Error;
use Exception;
use Laminas\Db\Adapter\Adapter;
use Laminas\EventManager\EventManager;
use Laminas\Http\Response;
use Laminas\Mvc\Application as LaminasApplication;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Laminas\Translator\TranslatorInterface;
use Laminas\View\Resolver\TemplateMapResolver;
use Library\Application as LegacyApplication;
use Model\Client\Client;
use Model\Group\Group;
use Nada\Database\AbstractDatabase;
use Nyholm\Psr7\ServerRequest;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;
use RuntimeException;
use Slim\Exception\HttpNotFoundException;

class MvcApplicationTest extends AbstractHttpControllerTestCase
{
    use ErrorHandlerTestTrait;

    private ServerRequestInterface $request;

    #[Before]
    public function createRequest()
    {
        $this->request = new ServerRequest('GET', '');
    }

    public function testServices()
    {
        $serviceManager = new ServiceManager();
        $eventManager = new EventManager();

        $application = $this->createStub(LaminasApplication::class);
        $application->method('getServiceManager')->willReturn($serviceManager);
        $application->method('getEventManager')->willReturn($eventManager);

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

        $mvcApplication = new MvcApplication($application, $container);
        $mvcApplication->configureServices();

        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get(AbstractDatabase::class));
        $this->assertSame($services[Adapter::class], $serviceManager->get(Adapter::class));
        $this->assertSame($services[AppConfig::class], $serviceManager->get(AppConfig::class));
        $this->assertSame($services[AssetUrlFunction::class], $serviceManager->get(AssetUrlFunction::class));
        $this->assertSame($services[Client::class], $serviceManager->get(Client::class));
        $this->assertSame($services[ClockInterface::class], $serviceManager->get(ClockInterface::class));
        $this->assertSame($services[Group::class], $serviceManager->get(Group::class));
        $this->assertSame($services[PathForRouteFunction::class], $serviceManager->get(PathForRouteFunction::class));
        $this->assertSame($services[TranslatorInterface::class], $serviceManager->get(TranslatorInterface::class));
        $this->assertSame($serviceManager, $serviceManager->get(ContainerInterface::class));
    }

    public function testRunConfiguresApplication()
    {
        $servicesConfigured = false;
        $eventsConfigured = false;

        $application = $this->createStub(LaminasApplication::class);
        $application->method('run')->willReturnCallback(
            function () use (&$servicesConfigured, &$eventsConfigured, $application) {
                $this->assertTrue($servicesConfigured);
                $this->assertTrue($eventsConfigured);
                return $application;
            }
        );
        $application->method('getMvcEvent')->willReturn(new MvcEvent());

        /** @var MockObject&MvcApplication */
        $mvcApplication = $this
            ->getMockBuilder(MvcApplication::class)
            ->setConstructorArgs([$application, new Container()])
            ->onlyMethods(['configureServices', 'configureEvents'])
            ->getMock();
        $mvcApplication->method('configureServices')->willReturnCallback(function () use (&$servicesConfigured) {
            $servicesConfigured = true;
        });
        $mvcApplication->method('configureEvents')->willReturnCallback(function () use (&$eventsConfigured) {
            $eventsConfigured = true;
        });

        $mvcApplication->run($this->request);
    }

    public function testRunReturnsMvcEvent()
    {
        $mvcEvent = null;

        $application = $this->createStub(LaminasApplication::class);
        $application->method('run')->willReturnCallback(
            function () use (&$mvcEvent, $application) {
                $mvcEvent = new MvcEvent();
                return $application;
            }
        );
        $application->method('getMvcEvent')->willReturnCallback(function () use (&$mvcEvent) {
            return $mvcEvent;
        });

        /** @var MockObject&MvcApplication */
        $mvcApplication = $this
            ->getMockBuilder(MvcApplication::class)
            ->setConstructorArgs([$application, new Container()])
            ->onlyMethods(['configureServices', 'configureEvents'])
            ->getMock();

        $result = $mvcApplication->run($this->request);
        $this->assertSame($mvcEvent, $result);
    }

    #[DoesNotPerformAssertions]
    public function testRunSuppressesLaminasWarning()
    {
        $application = $this->createStub(LaminasApplication::class);
        $application->method('run')->willReturnCallback(
            function () use ($application) {
                // Message starts with special string
                trigger_error(
                    'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a pony',
                    E_USER_DEPRECATED
                );
                return $application;
            }
        );
        $application->method('getMvcEvent')->willReturn(new MvcEvent());

        /** @var MockObject&MvcApplication */
        $mvcApplication = $this
            ->getMockBuilder(MvcApplication::class)
            ->setConstructorArgs([$application, new Container()])
            ->onlyMethods(['configureServices', 'configureEvents'])
            ->getMock();

        set_error_handler(function () {
            $this->fail('Warning should have been suppressed');
        });
        try {
            $mvcApplication->run($this->request);
        } finally {
            restore_error_handler();
        }
    }

    public function testRunKeepsOtherWarnings()
    {
        $application = $this->createStub(LaminasApplication::class);
        $application->method('run')->willReturnCallback(
            function () use ($application) {
                // Message starts with special string
                trigger_error(
                    'This warning must be let through',
                    E_USER_DEPRECATED
                );
                return $application;
            }
        );
        $application->method('getMvcEvent')->willReturn(new MvcEvent());

        /** @var MockObject&MvcApplication */
        $mvcApplication = $this
            ->getMockBuilder(MvcApplication::class)
            ->setConstructorArgs([$application, new Container()])
            ->onlyMethods(['configureServices', 'configureEvents'])
            ->getMock();

        $message = null;
        set_error_handler(function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;
            return true;
        });
        try {
            $mvcApplication->run($this->request);
        } finally {
            restore_error_handler();
        }
        $this->assertEquals('This warning must be let through', $message, 'Expected warning did not get through');
    }

    public function testRunResetsErrorHandlerOnError()
    {
        $application = $this->createMock(LaminasApplication::class);
        $application->method('run')->willReturnCallback(
            function () {
                throw new Error('Error thrown by run()');
            }
        );
        $application->expects($this->never())->method('getMvcEvent');

        /** @var MockObject&MvcApplication */
        $mvcApplication = $this
            ->getMockBuilder(MvcApplication::class)
            ->setConstructorArgs([$application, new Container()])
            ->onlyMethods(['configureServices', 'configureEvents'])
            ->getMock();

        $this->expectExceptionMessage('Error thrown by run()');
        $mvcApplication->run($this->request);
        // Error handler is verified by trait
    }

    private function createApplication(callable $action, array $templates): void
    {
        // Set application manually instead of having $this->getApplication()
        // create it, which would detach the standard SendResponseListener from
        // EVENT_FINISH. This would mask the effect of our own EVENT_FINISH
        // listener which, in the real application, prevents sending a response
        // by stopping event propagation before the SendResponseListener is
        // invoked.
        $this->application = LegacyApplication::init('Console');
        $serviceManager = $this->application->getServiceManager();

        // Minimal container stub with services required for MVC application
        // bootstrapping.
        $container = $this->createStub(Container::class);
        $container->method('get')->willReturnMap([
            [Adapter::class, $this->createStub(Adapter::class)],
            [AppConfig::class, $this->createStub(AppConfig::class)],
        ]);

        $mvcApplication = new MvcApplication($this->application, $container);
        $mvcApplication->configureServices();
        $mvcApplication->configureEvents();

        // The request would normally be passed to run(), which is bypassed in this test.
        (new ReflectionProperty($mvcApplication, 'request'))->setValue($mvcApplication, $this->request);

        // Set up a stub controller which will be mapped to the route.
        $controller = new class ($serviceManager->get(InjectTemplateListener::class), $action) extends AbstractActionController
        {
            public function __construct(
                private InjectTemplateListener $injectTemplateListener,
                private Closure $action,
            ) {
                // The template resolver is not designed to work with anonymous
                // controller classes. The NUL byte in the class name would
                // cause an exception. Disable the detection logic and use the
                // controller name ("test") from the route instead.
                $this->injectTemplateListener->setPreferRouteMatchController(true);
            }

            public function testAction()
            {
                return ($this->action)();
            }
        };
        /** @var ControllerManager */
        $controllerManager = $serviceManager->get(ControllerManager::class);
        // Use setFactory() instead of setService() to enable the necessary
        // initializers which inject the properly configured event manager.
        $controllerManager->setFactory('test', fn () => $controller);

        // Set up stub templates. The TemplatePathStack which is used by this
        // application for regular templates only works with a real filesystem,
        // not with stream wrappers. To avoid having to set up a filesystem
        // tree, add virtual files to the TemplateMapResolver which works with
        // stream wrappers too.
        /** @var TemplateMapResolver */
        $templateMap = $serviceManager->get(TemplateMapResolver::class);
        $root = vfsStream::setup();
        foreach ($templates as $name => $content) {
            $filename = str_replace('/', '_', $name);
            $template = vfsStream::newFile($filename)->at($root)->setContent($content)->url();
            $templateMap->add($name, $template);
        }
    }

    public function testFrameworkIntegrationSuccessWithTemplate()
    {
        $this->createApplication(
            fn () => ['message' => 'test'],
            ['test/test' => 'message: <?= $this->message ?>'],
        );

        $this->setTraceError(true);
        $this->dispatch('/console/test/test');
        $this->assertResponseStatusCode(200);
        $this->assertEquals('message: test', $this->getResponse()->getContent());
    }

    public function testFrameworkIntegrationSuccessWithRedirect()
    {
        $this->createApplication(
            function () {
                $response = new Response();
                $response->setStatusCode(302);
                $response->getHeaders()->addHeaderLine('Location', '/target');
                return $response;
            },
            [],
        );

        $this->setTraceError(true);
        $this->dispatch('/console/test/test');
        $this->assertResponseStatusCode(302);
        $this->assertResponseHeaderContains('Location', '/target');
    }

    public function testFrameworkIntegrationDispatchError()
    {
        $this->createApplication(
            function () {
                throw new Exception('Exception thrown by test action');
            },
            ['error/index' => 'error: <?= $this->exception->getMessage() ?>'], // overrides default template with the same name
        );

        $this->expectExceptionMessage('Exception thrown by test action');
        $this->dispatch('/console/test/test');
    }

    public function testFrameworkIntegrationRenderError()
    {
        $this->createApplication(
            fn () => null,
            [
                'test/test' => '<?php throw new \Exception("Exception thrown by template");',
                // overrides default template with the same name
                'error/index' => 'error: <?= $this->exception->getMessage() ?>',
            ],
        );

        $this->expectExceptionMessage('Exception thrown by template');
        $this->dispatch('/console/test/test');
    }

    public static function frameworkIntegrationInvalidRouteProvider()
    {
        return [
            ['/console/test/invalid', 'Invalid action'],
            ['/console/test/invalid/extra', 'No route matched.'],
            ['/console/invalid/invalid', 'Invalid controller name: invalid'],
        ];
    }

    #[DataProvider('frameworkIntegrationInvalidRouteProvider')]
    public function testFrameworkIntegrationInvalidRoute(string $route, string $message)
    {
        $this->createApplication(
            fn () => null,
            ['error/index' => 'error: not found'],
        );

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage($message);
        $this->dispatch($route);
    }

    public function testUnknownError()
    {
        // It's unclear if and how this condition could be triggered within the
        // framework. Test the listener directly.

        $mvcApplication = new MvcApplication(
            $this->createStub(LaminasApplication::class),
            $this->createStub(Container::class),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unknown error in MVC application.');
        $mvcApplication->preventErrorPage(new MvcEvent());
    }
}
