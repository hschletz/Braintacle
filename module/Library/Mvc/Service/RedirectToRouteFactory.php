<?php

namespace Library\Mvc\Service;

use Laminas\Mvc\Controller\Plugin\Redirect;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Library\Mvc\Controller\Plugin\RedirectToRoute;
use Library\Mvc\Controller\Plugin\UrlFromRoute;
use Psr\Container\ContainerInterface;

/**
 * Factory for RedirectToRoute controller plugin
 */
class RedirectToRouteFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $pluginManager = $container->get(PluginManager::class);
        return new RedirectToRoute(
            $pluginManager->get(Redirect::class),
            $pluginManager->get(UrlFromRoute::class)
        );
    }
}
