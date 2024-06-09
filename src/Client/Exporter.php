<?php

namespace Braintacle\Client;

use Laminas\Hydrator\HydratorInterface;
use Protocol\Hydrator\DatabaseProxy;
use Psr\Container\ContainerInterface;

/**
 * Export client as XML.
 */
class Exporter
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function getHydrator(string $name): HydratorInterface
    {
        $class = 'Protocol\Hydrator\\' . $name;
        if (class_exists($class)) {
            $hydrator = $this->container->get($class);
        } else {
            $tableClass = 'Database\Table\\' . $name;
            $table = $this->container->get($tableClass);
            $hydrator = new DatabaseProxy($table->getHydrator());
        }

        return $hydrator;
    }
}
