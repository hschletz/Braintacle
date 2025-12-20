<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Legacy\Controller;
use Laminas\Stdlib\DispatchableInterface;
use Override;

/**
 * PluginInterface implementation.
 */
trait ControllerPluginTrait
{
    private Controller $controller;

    #[Override]
    public function setController(DispatchableInterface $controller)
    {
        $this->controller = $controller;
    }

    #[Override]
    public function getController()
    {
        return $this->controller;
    }
}
