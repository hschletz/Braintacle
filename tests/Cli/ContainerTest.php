<?php

namespace Braintacle\Test\Cli;

use Braintacle\Cli\Command\BuildCommand;
use Braintacle\Cli\Command\DatabaseCommand;
use Braintacle\Cli\Command\DecodeCommand;
use Braintacle\Cli\Command\ExportCommand;
use Braintacle\Cli\Command\ImportCommand;
use Braintacle\Cli\Container;
use Braintacle\Cli\ToolsApplication;
use Braintacle\Container as BaseContainer;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

#[CoversClass(Container::class)]
#[UsesClass(BaseContainer::class)]
#[UsesClass(ToolsApplication::class)]
#[RequiresPhp('>= 8.4')]
final class ContainerTest extends TestCase
{
    #[TestWith([BuildCommand::class])]
    #[TestWith([DatabaseCommand::class])]
    #[TestWith([DecodeCommand::class])]
    #[TestWith([ExportCommand::class])]
    #[TestWith([ImportCommand::class])]
    public function testLazyService(string $name)
    {
        $container = new Container();
        $service = $container->get($name);
        $reflector = new ReflectionObject($service);
        $this->assertTrue($reflector->isUninitializedLazyObject($service));
    }

    public function testFailureWithoutLazyService()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service Braintacle\Cli\Command\Test must be explicitly defined as lazy');

        $container = new Container();
        $container->get('Braintacle\Cli\Command\Test');
    }
}
