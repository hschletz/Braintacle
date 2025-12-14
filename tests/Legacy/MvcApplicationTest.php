<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Test\ErrorHandlerTestTrait;
use Error;
use Exception;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Laminas\Uri\Http as Uri;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Override;
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

        $routeMatch = new RouteMatch(['controller' => '_controller', 'action' => '_action']);

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->with($mvcEvent->getRequest())->willReturn($routeMatch);
        $mvcEvent->setRouter($router);

        $controller->expects($this->once())->method('setEvent')->with($mvcEvent);

        $application = $this->createStub(Application::class);
        $application->method('getMvcEvent')->willReturn($mvcEvent);

        $controllerManager = $this->createMock(ControllerManager::class);
        $controllerManager->method('has')->with('_controller')->willReturn(true);
        $controllerManager->method('get')->with('_controller')->willReturn($controller);

        $mvcApplication = new MvcApplication($application, $controllerManager, $phpRenderer);
        $returnedEvent = $mvcApplication->run($this->createStub(ServerRequestInterface::class));

        $this->assertSame($routeMatch, $mvcEvent->getRouteMatch());

        return $returnedEvent;
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
        $phpRenderer->method('render')->with('console/_controller/_action', $result)->willReturn('_content');

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
            ['/console/_controller/invalid', 'Invalid action'],
            ['/console/_controller/_action/extra', 'No route matched.'],
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

        $application = $this->createStub(Application::class);
        $application->method('getMvcEvent')->willReturn($mvcEvent);

        $controller = new class extends Controller {
            #[Override]
            public function dispatch(RequestInterface $request, ?ResponseInterface $response = null)
            {
                $routeMatch = $this->getEvent()->getRouteMatch();
                if ($routeMatch->getParam('action') != '_action') {
                    $routeMatch->setParam('action', 'not-found');
                }

                return [];
            }
        };

        $controllerManager = $this->createStub(ControllerManager::class);
        $controllerManager->method('has')->willReturnCallback(fn($name) => $name == '_controller');
        $controllerManager->method('get')->willReturn($controller);

        $phpRenderer = $this->createStub(PhpRenderer::class);

        $mvcApplication = new MvcApplication($application, $controllerManager, $phpRenderer);

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage($message);

        $mvcApplication->run($this->createStub(ServerRequestInterface::class));
    }

    public function testGetMvcEvent()
    {
        $mvcEvent = new MvcEvent();

        $application = $this->createStub(Application::class);
        $application->method('getMvcEvent')->willReturn($mvcEvent);

        $controllerManager = $this->createStub(ControllerManager::class);
        $phpRenderer = $this->createStub(PhpRenderer::class);

        $mvcApplication = new MvcApplication($application, $controllerManager, $phpRenderer);
        $this->assertSame($mvcEvent, $mvcApplication->getMvcEvent());
    }
}
