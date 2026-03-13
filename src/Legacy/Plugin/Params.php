<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Legacy\Request;

/**
 * Replacement for builtin Params plugin.
 */
final class Params
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
            /** @var ?string */
            return $this->request->getQuery($name, $default);
        }
    }

    public function fromPost(?string $name = null): array|string|null
    {
        if ($name === null) {
            return $this->request->getPost()->toArray();
        } else {
            /** @var ?string */
            return $this->request->getPost($name);
        }
    }
}
