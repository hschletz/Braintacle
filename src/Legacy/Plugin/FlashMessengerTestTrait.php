<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\FlashMessages;

/**
 * Helper for controller tests using the FlashMessenger plugin.
 *
 * @codeCoverageIgnore
 */
trait FlashMessengerTestTrait
{
    protected array $flashMessages;

    public function initFlashMessages()
    {
        $this->flashMessages = [];

        $stub = $this->createStub(FlashMessages::class);
        $stub->method('add')->willReturnCallback(function (string $type, string $message) {
            $this->flashMessages[$type][] = $message;
        });
        $stub->method('get')->willReturnCallback(function (string $type): array {
            return $this->flashMessages[$type] ?? [];
        });
        $this->getApplicationServiceLocator()->setService(FlashMessages::class, $stub);
    }
}
