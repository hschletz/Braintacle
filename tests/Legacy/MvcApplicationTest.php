<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\ApplicationService;
use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Test\ErrorHandlerTestTrait;
use Console\Controller\ClientController;
use Error;
use Exception;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Uri\Http as Uri;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Translator\TranslatorInterface;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use LogicException;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

#[CoversClass(MvcApplication::class)]
final class MvcApplicationTest extends TestCase
{
    use ErrorHandlerTestTrait;

    private ?TranslatorInterface $translatorBackup;

    #[Before]
    public function backupTranslator()
    {
        $this->translatorBackup = AbstractValidator::getDefaultTranslator();
    }

    #[After]
    public function restoreTranslator()
    {
        AbstractValidator::setDefaultTranslator($this->translatorBackup);
    }

    private function createMvcApplication(
        ?ApplicationService $applicationService = null,
        ?PluginManager $pluginManager = null,
        ?PhpRenderer $phpRenderer = null,
        ?TranslatorInterface $translator = null,
    ): MvcApplication {
        return new MvcApplication(
            $applicationService ?? $this->createStub(ApplicationService::class),
            $pluginManager ?? $this->createStub(PluginManager::class),
            $phpRenderer ?? $this->createStub(PhpRenderer::class),
            $translator ?? $this->createStub(TranslatorInterface::class),
        );
    }

    private function getMvcEvent(
        MockObject & Controller $controller,
        ?PhpRenderer $phpRenderer = null,
        ?MvcEvent $mvcEvent = null,
    ): MvcEvent {
        if (!$phpRenderer) {
            $phpRenderer = $this->createStub(PhpRenderer::class);
        }
        if (!$mvcEvent) {
            $mvcEvent = new MvcEvent();
            $mvcEvent->setRequest(new Request());
            $mvcEvent->setResponse(new Response());
        }

        $routeMatch = new RouteMatch(['controller' => 'client', 'action' => '_action']);

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->with($mvcEvent->getRequest())->willReturn($routeMatch);
        $mvcEvent->setRouter($router);

        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->expects($this->once())->method('setController')->with($controller);

        $controller->expects($this->once())->method('setEvent')->with($mvcEvent);
        $controller->expects($this->once())->method('setPluginManager')->with($pluginManager);

        $serviceManager = new ServiceManager(['services' => [ClientController::class => $controller]]);

        $applicationService = $this->createStub(ApplicationService::class);
        $applicationService->method('getMvcEvent')->willReturn($mvcEvent);
        $applicationService->method('getServiceManager')->willReturn($serviceManager);

        $mvcApplication = $this->createMvcApplication($applicationService, $pluginManager, $phpRenderer);
        $returnedEvent = $mvcApplication->run($this->createStub(ServerRequestInterface::class));

        $this->assertSame($routeMatch, $mvcEvent->getRouteMatch());

        return $returnedEvent;
    }

    public function testRunInjectsTranslator()
    {
        $translator = $this->createStub(TranslatorInterface::class);

        $applicationService = $this->createStub(ApplicationService::class);
        $applicationService->method('getMvcEvent')->willThrowException(new Exception('Abort early'));

        $mvcApplication = $this->createMvcApplication(
            applicationService: $applicationService,
            translator: $translator,
        );

        try {
            $mvcApplication->run($this->createStub(ServerRequestInterface::class));
        } catch (Exception $exception) {
            if ($exception->getMessage() != 'Abort early') {
                throw $exception;
            }
        }

        $this->assertSame($translator, AbstractValidator::getDefaultTranslator());
    }

    public function testRunReturningResponse()
    {
        $request = new Request();
        $response = new Response();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);

        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->with($request)->willReturnCallback(function () use ($mvcEvent, $response) {
            $mvcEvent->setResponse($response); // simulate Redirect controller plugin

            return $response;
        });

        $phpRenderer = $this->createMock(PhpRenderer::class);
        $phpRenderer->expects($this->never())->method('render');

        $returnedMvcEvent = $this->getMvcEvent($controller, $phpRenderer, $mvcEvent);
        $this->assertSame($response, $returnedMvcEvent->getResponse());
    }

    public function testRunReturningArray()
    {
        $request = new Request();
        $result = ['foo' => 'bar'];

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);

        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->with($request)->willReturnCallback(function () use ($mvcEvent, $result) {
            $mvcEvent->setResponse(new Response());

            return $result;
        });

        $phpRenderer = $this->createMock(PhpRenderer::class);
        $phpRenderer->method('render')->with(
            $this->callback(function ($viewModel) {
                $this->assertInstanceOf(ViewModel::class, $viewModel);
                $this->assertEquals('console/client/_action', $viewModel->getTemplate());

                return true;
            }),
            null,
        )->willReturn('_content');

        $returnedMvcEvent = $this->getMvcEvent($controller, $phpRenderer, $mvcEvent);

        /** @var Response */
        $response = $returnedMvcEvent->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('_content', $response->getContent());
    }

    public function testRunReturningViewModel()
    {
        $request = new Request();
        $viewModel = new ViewModel();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);

        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->with($request)->willReturnCallback(function () use ($mvcEvent, $viewModel) {
            $mvcEvent->setResponse(new Response());

            return $viewModel;
        });

        $phpRenderer = $this->createMock(PhpRenderer::class);
        $phpRenderer->method('render')->with($viewModel, null)->willReturn('_content');

        $returnedMvcEvent = $this->getMvcEvent($controller, $phpRenderer, $mvcEvent);

        /** @var Response */
        $response = $returnedMvcEvent->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('_content', $response->getContent());
    }

    public function testRunReportsMissingVariable()
    {
        $request = new Request();
        $result = [];

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);

        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->with($request)->willReturnCallback(function () use ($mvcEvent, $result) {
            $mvcEvent->setResponse(new Response());

            return $result;
        });

        $phpRenderer = $this->createMock(PhpRenderer::class);
        $phpRenderer->method('render')->willReturnCallback(function (ViewModel $viewModel) {
            /** @psalm-suppress UnusedMethodCall (call should cause an exception) */
            $viewModel->getVariables()['invalid'];
        });

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('View variable "invalid" does not exist');

        $this->getMvcEvent($controller, $phpRenderer, $mvcEvent);
    }

    public function testRunSuppressesLaminasWarning()
    {
        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->willReturnCallback(
            function () {
                // Message starts with special string
                trigger_error(
                    'Laminas\ServiceManager\AbstractPluginManager::__construct now expects a pony',
                    E_USER_DEPRECATED
                );

                return [];
            }
        );

        set_error_handler(function () {
            $this->fail('Warning should have been suppressed');
        });
        try {
            $this->getMvcEvent($controller);
        } finally {
            restore_error_handler();
        }
    }

    public function testRunKeepsOtherWarnings()
    {
        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->willReturnCallback(
            function () {
                // Message starts without special string
                trigger_error(
                    'This warning must be let through',
                    E_USER_DEPRECATED
                );

                return [];
            }
        );

        $message = null;
        set_error_handler(function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;
            return true;
        });
        try {
            $this->getMvcEvent($controller);
        } finally {
            restore_error_handler();
        }
        $this->assertEquals('This warning must be let through', $message, 'Expected warning did not get through');
    }

    public function testRunResetsErrorHandlerOnError()
    {
        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->willReturnCallback(
            function () {
                throw new Error('Error thrown by run()');
            }
        );

        $this->expectExceptionMessage('Error thrown by run()');
        $this->getMvcEvent($controller);
        // Error handler is verified by trait
    }

    public function testRunThrowsInController()
    {
        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->willThrowException(new Exception('Exception thrown by test action'));

        $this->expectExceptionMessage('Exception thrown by test action');
        $this->getMvcEvent($controller);
    }

    public function testRunThrowsInRenderer()
    {
        $controller = $this->createMock(Controller::class);
        $controller->method('dispatch')->willReturn([]);

        $phpRenderer = $this->createStub(PhpRenderer::class);
        $phpRenderer->method('render')->willThrowException(new Exception('Exception thrown by test action'));

        $this->expectExceptionMessage('Exception thrown by test action');
        $this->getMvcEvent($controller, $phpRenderer);
    }

    public static function invalidRouteProvider()
    {
        return [
            ['/console/client/_action/extra', 'No route matched.'],
            ['/console/invalid/_action', 'Invalid controller name: invalid'],
        ];
    }

    #[DataProvider('invalidRouteProvider')]
    public function testFrameworkIntegrationInvalidRoute(string $route, string $message)
    {
        $request = new Request();
        $request->setUri(new Uri($route));

        $mvcEvent = new MvcEvent();
        $mvcEvent->setRequest($request);
        $mvcEvent->setRouter(TreeRouteStack::factory([
            'routes' => [
                'console' => [
                    'type' => 'segment',
                    'options' => [
                        'route' => '/[console[/]][:controller[/][:action[/]]]',
                    ],
                ],
            ],
        ]));

        $serviceManager = new ServiceManager();

        $applicationService = $this->createStub(ApplicationService::class);
        $applicationService->method('getMvcEvent')->willReturn($mvcEvent);
        $applicationService->method('getServiceManager')->willReturn($serviceManager);

        $phpRenderer = $this->createStub(PhpRenderer::class);

        $mvcApplication = $this->createMvcApplication($applicationService, phpRenderer: $phpRenderer);

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage($message);

        $mvcApplication->run($this->createStub(ServerRequestInterface::class));
    }

    public function testGetMvcEvent()
    {
        $mvcEvent = new MvcEvent();

        $applicationService = $this->createStub(ApplicationService::class);
        $applicationService->method('getMvcEvent')->willReturn($mvcEvent);

        $phpRenderer = $this->createStub(PhpRenderer::class);

        $mvcApplication = $this->createMvcApplication($applicationService, phpRenderer: $phpRenderer);
        $this->assertSame($mvcEvent, $mvcApplication->getMvcEvent());
    }
}
