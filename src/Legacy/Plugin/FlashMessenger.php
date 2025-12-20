<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\FlashMessages;
use Laminas\Mvc\Controller\Plugin\PluginInterface;
use Laminas\View\Helper\EscapeHtml;

/**
 * Drop-in replacement for FlashMessenger controller plugin and view helper.
 */
final class FlashMessenger implements PluginInterface
{
    use ControllerPluginTrait;

    public function __construct(
        private FlashMessages $flashMessages,
        private EscapeHtml $escapeHtml,
    ) {}

    public function __invoke(): self
    {
        return $this;
    }

    public function addMessage(string $message, string $namespace): void
    {
        $this->flashMessages->add($namespace, $message);
    }

    public function addErrorMessage(string $message): void
    {
        $this->flashMessages->add(FlashMessages::Error, $message);
    }

    public function addSuccessMessage(string $message): void
    {
        $this->flashMessages->add(FlashMessages::Success, $message);
    }

    public function getMessagesFromNamespace(string $namespace): array
    {
        return $this->flashMessages->get($namespace);
    }

    public function render(string $namespace): string
    {
        $messages = $this->flashMessages->get($namespace);
        if ($messages) {
            assert(count($messages) == 1);
            return sprintf('<ul class="%s"><li>%s</li></ul>', $namespace, ($this->escapeHtml)($messages[0]));
        } else {
            return '';
        }
    }
}
