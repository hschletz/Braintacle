<?php

namespace Braintacle\Test\Legacy;

use Braintacle\Legacy\ClientFactory;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

class ClientFactoryTest extends TestCase
{
    public function testFactory()
    {
        $container = $this->createStub(ContainerInterface::class);

        $factory = new ClientFactory();
        $instance = $factory($container);

        $this->assertInstanceOf(Client::class, $instance);

        $property = new ReflectionProperty($instance, 'container');
        $this->assertSame($container, $property->getValue($instance));
    }
}
