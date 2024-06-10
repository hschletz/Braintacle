<?php

namespace Braintacle\Test;

use Braintacle\Container;
use Model\Package\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

// Factories are tested separately where feasible.
class ContainerTest extends TestCase
{
    public function testInterfaceAliases()
    {
        $container = new Container();
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $container->get(ResponseInterface::class));
        $this->assertInstanceOf(StorageInterface::class, $container->get(StorageInterface::class));
    }
}
