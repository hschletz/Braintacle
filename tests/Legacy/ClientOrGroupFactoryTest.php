<?php

namespace Braintacle\Legacy;

use DI\Factory\RequestedEntry;
use Model\Client\Client;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

class ClientOrGroupFactoryTest extends TestCase
{
    public static function factoryProvider()
    {
        return [[Client::class], [Group::class]];
    }

    #[DataProvider('factoryProvider')]
    public function testFactory(string $class)
    {
        $requestedEntry = $this->createStub(RequestedEntry::class);
        $requestedEntry->method('getName')->willReturn($class);

        $container = $this->createStub(ContainerInterface::class);

        $factory = new ClientOrGroupFactory();
        $instance = $factory($requestedEntry, $container);

        $this->assertInstanceOf($class, $instance);

        $property = new ReflectionProperty($instance, 'container');
        $this->assertSame($container, $property->getValue($instance));
    }
}
