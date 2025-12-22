<?php

namespace Braintacle\Legacy\Plugin;

use Laminas\Http\Request;
use Laminas\Mvc\Controller\Plugin\PluginInterface;

/**
 * Replacement for builtin Params plugin.
 */
final class Params implements PluginInterface
{
    use ControllerPluginTrait;

    public function __construct(private Request $request) {}

    public function __invoke(): self
    {
        return $this;
    }

    public function fromQuery(?string $name = null, ?string $default = null): array|string|null
    {
        if ($name === null) {
            return $this->request->getQuery()->toArray();
        } else {
            return $this->request->getQuery($name, $default);
        }
    }

    public function fromPost(?string $name = null): array|string|null
    {
        if ($name === null) {
            return $this->request->getPost()->toArray();
        } else {
            return $this->request->getPost($name);
        }
    }

    public function fromFiles(): array
    {
        return $this->request->getFiles()->toArray();
    }
}
