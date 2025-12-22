<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Legacy\Controller;
use Laminas\Stdlib\DispatchableInterface;

/**
 * PluginInterface implementation.
 */
trait ControllerPluginTrait
{
    private Controller $controller;

    public function setController(DispatchableInterface $controller)
    {
        $this->controller = $controller;
    }

    public function getController()
    {
        return $this->controller;
    }
}
