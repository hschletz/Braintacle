<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\Exporter;
use Database\AbstractTable;
use Laminas\Hydrator\HydratorInterface;
use Protocol\Hydrator\Filesystems as FilesystemsHydrator;
use PHPUnit\Framework\Attributes\DataProvider;
use Protocol\Hydrator\DatabaseProxy;
use Protocol\Hydrator\Software as SoftwareHydrator;
use Psr\Container\ContainerInterface;

class ExporterTest extends \PHPUnit\Framework\TestCase
{
    public static function getHydratorWithDedicatedHydratorProvider()
    {
        return [
            ['Filesystems', FilesystemsHydrator::class],
            ['Software', SoftwareHydrator::class],
        ];
    }

    #[DataProvider('getHydratorWithDedicatedHydratorProvider')]
    public function testGetHydratorWithDedicatedHydrator(string $name, string $class)
    {
        $hydrator = $this->createStub($class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($class)->willReturn($hydrator);

        $exporter = new Exporter($container);
        $this->assertInstanceOf($class, $exporter->getHydrator($name));
    }

    public static function getHydratorWithDatabaseProxyProvider()
    {
        return [
            ['AudioDevices'],
            ['Controllers'],
            ['Cpu'],
            ['DisplayControllers'],
            ['Displays'],
            ['ExtensionSlots'],
            ['InputDevices'],
            ['MemorySlots'],
            ['Modems'],
            ['MsOfficeProducts'],
            ['NetworkInterfaces'],
            ['Ports'],
            ['Printers'],
            ['RegistryData'],
            ['Sim'],
            ['StorageDevices'],
            ['VirtualMachines'],
        ];
    }

    #[DataProvider('getHydratorWithDatabaseProxyProvider')]
    public function testGetHydratorWithDatabaseProxy($name)
    {
        $databaseHydrator = $this->createStub(HydratorInterface::class);

        $table = $this->createStub(AbstractTable::class);
        $table->method('getHydrator')->willReturn($databaseHydrator);

        $tableClass = 'Database\Table\\' . $name;
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($tableClass)->willReturn($table);

        $exporter = new Exporter($container);

        /** @var DatabaseProxy */
        $hydrator = $exporter->getHydrator($name);
        $this->assertInstanceOf(DatabaseProxy::class, $hydrator);
        $this->assertSame($databaseHydrator, $hydrator->getHydrator());
    }
}
