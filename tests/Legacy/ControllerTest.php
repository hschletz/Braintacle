<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcApplication;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;

#[CoversClass(Controller::class)]
final class ControllerTest extends TestCase
{
    public function testDispatch()
    {
        $mvcEvent = new MvcEvent();
        $mvcEvent->setParam('id', 'testDispatch');

        $request = new Request();

        $controller = new class extends Controller
        {
            #[Override]
            public function onDispatch(MvcEvent $e)
            {
                // Should be the same instance that was passed to dispatch()
                TestCase::assertEquals('testDispatch', $e->getParam('id'));

                // Properties set within dispatch() before this method got invoked
                TestCase::assertEquals(MvcEvent::EVENT_DISPATCH, $e->getName());
                TestCase::assertSame($this->request, $e->getRequest());
                TestCase::assertSame($this->response, $e->getResponse());
                TestCase::assertSame($this, $e->getTarget());

                // Should be the same instance that was passed to dispatch()
                return $this->request;
            }
        };
        $controller->setEvent($mvcEvent);

        $this->assertSame($request, $controller->dispatch($request));
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
}
