<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\Controller;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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
}
