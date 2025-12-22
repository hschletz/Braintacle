<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Legacy\MvcEvent;
use Braintacle\Legacy\Plugin\PluginManager;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Http\Request;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

#[CoversClass(Controller::class)]
#[UsesClass(MvcEvent::class)]
final class ControllerTest extends TestCase
{
    public function testPlugin()
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->method('get')->with('test')->willReturn(new class {
            public function __invoke(string $foo, string $bar): string
            {
                return $foo . $bar;
            }
        });

        $controller = new class extends Controller {};
        $controller->setPluginManager($pluginManager);

        // @phpstan-ignore method.notFound (magic method)
        $this->assertEquals('foobar', $controller->test('foo', 'bar'));
    }

    public function testEvent()
    {
        $response = new Response();

        $mvcEvent = new MvcEvent();
        $mvcEvent->setResponse($response);

        $controller = new class extends Controller {};
        $controller->setEvent($mvcEvent);
        $this->assertSame($mvcEvent, $controller->getEvent());
        $this->assertSame($response, $controller->getResponse());
    }

    public function testDispatch()
    {
        $request = new Request();

        $controller = $this->createPartialMock(Controller::class, ['getEvent', 'onDispatch']);
        $controller->method('getEvent')->willReturn(new MvcEvent());
        $controller->method('onDisPatch')->with($this->callback(
            function (MvcEvent $mvcEvent) use ($controller, $request): bool {
                $this->assertSame($request, $mvcEvent->getRequest());
                $this->assertSame($request, $controller->getRequest());

                return true;
            }
        ))->willReturn('_result');

        $this->assertEquals('_result', $controller->dispatch($request));
    }

    public function testOnDispatch()
    {
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRouteMatch(new RouteMatch(['action' => 'test']));

        $controller = new class extends Controller {
            public function testAction()
            {
                return ['foo' => 'bar'];
            }
        };
        $result = $controller->onDispatch($mvcEvent);
        $this->assertEquals(['foo' => 'bar'], $result);
        $this->assertEquals(['foo' => 'bar'], $mvcEvent->getResult());
    }

    public function testOnDispatchInvalidAction()
    {
        $mvcEvent = new MvcEvent();
        $mvcEvent->setRouteMatch(new RouteMatch(['action' => 'test']));
        $mvcEvent->setParam(MvcApplication::Psr7Request, $this->createStub(ServerRequestInterface::class));

        $controller = new class extends Controller {};

        $this->expectException(HttpNotFoundException::class);
        $this->expectExceptionMessage('Invalid action');

        $controller->onDispatch($mvcEvent);
    }

    public function testGetMethodFromAction()
    {
        $controller = new class extends Controller {};
        $this->assertEquals('testAction', $controller->getMethodFromAction('test'));
    }
}
