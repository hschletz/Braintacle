<?php

namespace Braintacle\Legacy\Plugin;

use Laminas\Mvc\Controller\Plugin\PluginInterface;

/**
 * Replacement for builtin Params plugin.
 */
final class Params implements PluginInterface
{
    use ControllerPluginTrait;

    public function __invoke(): self
    {
        return $this;
    }

    public function fromQuery(?string $name = null, ?string $default = null): array|string|null
    {
        if ($name === null) {
            return $this->controller->getRequest()->getQuery()->toArray();
        } else {
            return $this->controller->getRequest()->getQuery($name, $default);
        }
    }

    public function fromPost(?string $name = null): array|string|null
    {
        if ($name === null) {
            return $this->controller->getRequest()->getPost()->toArray();
        } else {
            return $this->controller->getRequest()->getPost($name);
        }
    }

    public function fromFiles(): array
    {
        return $this->controller->getRequest()->getFiles()->toArray();
    }
}
