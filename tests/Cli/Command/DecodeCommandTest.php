<?php

namespace Braintacle\Test\Cli\Command;

use Braintacle\Cli\Command\DecodeCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Protocol\Filter\InventoryDecode;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(DecodeCommand::class)]
final class DecodeCommandTest extends TestCase
{
    use CommandTesterTrait;

    private const Name = 'decode';

    private function createCommand(
        ?InventoryDecode $inventoryDecodeFilter = null,
        ?Filesystem $filesystem = null,
    ): DecodeCommand {
        return new DecodeCommand(
            $inventoryDecodeFilter ?? $this->createStub(InventoryDecode::class),
            $filesystem ?? $this->createStub(Filesystem::class),
        );
    }

    public function testDecodeToStdOut()
    {
        $inventoryDecodeFilter = $this->createMock(InventoryDecode::class);
        $inventoryDecodeFilter->method('filter')->with('encoded')->willReturn('decoded');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('_inputFile')->willReturn('encoded');
        $filesystem->expects($this->never())->method('dumpFile');

        $command = $this->createCommand($inventoryDecodeFilter, $filesystem);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute(['input file' => '_inputFile']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertEquals('decoded', $commandTester->getDisplay());
    }

    public function testDecodeToFile()
    {
        $inventoryDecodeFilter = $this->createMock(InventoryDecode::class);
        $inventoryDecodeFilter->method('filter')->with('encoded')->willReturn('decoded');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('readFile')->with('_inputFile')->willReturn('encoded');
        $filesystem->expects($this->once())->method('dumpFile')->with('_outputFile', 'decoded');

        $command = $this->createCommand($inventoryDecodeFilter, $filesystem);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute(['input file' => '_inputFile', 'output file' => '_outputFile']);

        $commandTester->assertCommandIsSuccessful();
        $this->assertEmpty($commandTester->getDisplay());
    }

    public function testMissingArgument()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->never())->method('dumpFile');

        $command = $this->createCommand(filesystem: $filesystem);
        $commandTester = $this->createCommandTester(self::Name, $command);

        try {
            $commandTester->execute([]);
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException) {
            $this->assertEmpty($commandTester->getDisplay());
        }
    }
}
