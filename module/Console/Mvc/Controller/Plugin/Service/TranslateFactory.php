<?php

namespace Console\Mvc\Controller\Plugin\Service;

use Console\Mvc\Controller\Plugin\Translate;
use Laminas\Mvc\I18n\Translator;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class TranslateFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new Translate($container->get(Translator::class));
    }
}
