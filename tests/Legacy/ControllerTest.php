<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcApplication;
use Laminas\Http\Request;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use stdClass;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    public function testPluginCallable()
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

    public function testPluginNotCallable()
    {
        $plugin = new stdClass();

        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->method('get')->with('test')->willReturn($plugin);

        $controller = new class extends Controller {};
        $controller->setPluginManager($pluginManager);

        // @phpstan-ignore method.notFound (magic method)
        $this->assertSame($plugin, $controller->test());
    }

    public function testEvent()
    {
        $mvcEvent = new MvcEvent();

        $controller = new class extends Controller {};
        $controller->setEvent($mvcEvent);
        $this->assertSame($mvcEvent, $controller->getEvent());
    }

    public function testDispatch()
    {
        $request = new Request();

        $controller = $this->createPartialMock(Controller::class, ['getEvent', 'onDispatch']);
        $controller->method('getEvent')->willReturn(new MvcEvent());
        $controller->method('onDisPatch')->with($this->callback(
            function (MvcEvent $mvcEvent) use ($controller, $request): bool {
                $this->assertEquals(MvcEvent::EVENT_DISPATCH, $mvcEvent->getName());
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
                return '_result';
            }
        };
        $result = $controller->onDispatch($mvcEvent);
        $this->assertEquals('_result', $result);
        $this->assertEquals('_result', $mvcEvent->getResult());
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
