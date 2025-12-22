<?php

namespace Braintacle\Legacy\Plugin;

use Braintacle\Legacy\Controller;
use Console\Mvc\Controller\Plugin\GetOrder;
use Console\Mvc\Controller\Plugin\PrintForm;
use Console\Mvc\Controller\Plugin\Translate;
use Psr\Container\ContainerInterface;

/**
 * Replacement for controller plugin manager.
 *
 * Instances are not cached. Subsequent invocations will return a new plugin
 * instance. This is not a problem because all plugins are initialized in get()
 * and otherwise stateless.
 */
final class PluginManager
{
    private Controller $controller;

    public function __construct(private ContainerInterface $container) {}

    public function setController(Controller $controller): void
    {
        $this->controller = $controller;
    }

    public function get(string $name): callable
    {
        $plugin = $this->container->get(match ($name) {
            '_' => Translate::class,
            'flashMessenger' => FlashMessenger::class,
            'getOrder' => GetOrder::class,
            'params' => Params::class,
            'printForm' => PrintForm::class,
            'redirectToRoute' => RedirectToRoute::class,
        });
        $plugin->setController($this->controller);

        return $plugin;
    }
}
