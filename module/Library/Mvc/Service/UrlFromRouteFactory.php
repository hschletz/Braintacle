<?php

namespace Library\Mvc\Service;

use Laminas\Mvc\Controller\Plugin\Url;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Library\Mvc\Controller\Plugin\UrlFromRoute;
use Psr\Container\ContainerInterface;

/**
 * Factory for UrlFromRoute controller plugin
 */
class UrlFromRouteFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $urlPlugin = $container->get(PluginManager::class)->get(Url::class);
        return new UrlFromRoute($urlPlugin);
    }
}
