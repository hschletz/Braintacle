<?php

namespace Braintacle\Test\Legacy\Plugin;

use Braintacle\FlashMessages;
use Braintacle\Legacy\Controller;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Laminas\View\Helper\EscapeHtml;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FlashMessenger::class)]
final class FlashMessengerTest extends TestCase
{
    private function createFlashMessenger(
        ?FlashMessages $flashMessages = null,
        ?EscapeHtml $escapeHtml = null,
    ): FlashMessenger {
        return new FlashMessenger(
            $flashMessages ?? $this->createStub(FlashMessages::class),
            $escapeHtml ?? $this->createStub(EscapeHtml::class),
        );
    }

    public function testController()
    {
        $controller = $this->createStub(Controller::class);
        $flashMessenger = $this->createFlashMessenger();
        $flashMessenger->setController($controller);
        $this->assertSame($controller, $flashMessenger->getController());
    }

    public function testInvoke()
    {
        $flashMessenger = $this->createFlashMessenger();
        $this->assertSame($flashMessenger, $flashMessenger());
    }

    public function testAddMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with('custom', 'message');

        $this->createFlashMessenger(flashMessages: $flashMessages)->addMessage('message', 'custom');
    }

    public function testAddErrorMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with(FlashMessages::Error, 'message');

        $this->createFlashMessenger(flashMessages: $flashMessages)->addErrorMessage('message');
    }

    public function testAddSuccessMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with(FlashMessages::Success, 'message');

        $this->createFlashMessenger(flashMessages: $flashMessages)->addSuccessMessage('message');
    }

    public function testGetMessagesFromNamespace()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with('namespace')->willReturn(['message']);

        $this->assertEquals(
            ['message'],
            $this->createFlashMessenger(flashMessages: $flashMessages)->getMessagesFromNamespace('namespace')
        );
    }

    public function testRenderWithMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with('namespace')->willReturn(['message']);

        $escapeHtml = $this->createStub(EscapeHtml::class);
        $escapeHtml->method('__invoke')->willReturnCallback(fn(string $message) => strtoupper($message));

        $this->assertEquals(
            '<ul class="namespace"><li>MESSAGE</li></ul>',
            $this->createFlashMessenger(flashMessages: $flashMessages, escapeHtml: $escapeHtml)->render('namespace')
        );
    }

    public function testRenderWithoutMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with('namespace')->willReturn([]);

        $this->assertEquals(
            '',
            $this->createFlashMessenger(flashMessages: $flashMessages)->render('namespace')
        );
    }
}
