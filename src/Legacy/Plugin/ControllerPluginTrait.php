<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Legacy\Controller;

/**
 * PluginInterface implementation.
 */
trait ControllerPluginTrait
{
    private Controller $controller;

    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function getController()
    {
        return $this->controller;
    }
}
