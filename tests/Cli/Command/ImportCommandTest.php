<?php

namespace Braintacle\Test\Cli\Command;

use Braintacle\Cli\Command\ImportCommand;
use Braintacle\Client\Import\Importer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

#[CoversClass(ImportCommand::class)]
final class ImportCommandTest extends TestCase
{
    use CommandTesterTrait;

    private const Name = 'import';

    private function createCommand(?Importer $importer = null): ImportCommand
    {
        return new ImportCommand($importer ?? $this->createStub(Importer::class));
    }

    public function testImport()
    {
        $importer = $this->createMock(Importer::class);
        $importer->expects($this->once())->method('importFile')->with('input file');

        $command = $this->createCommand($importer);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute(['filename' => 'input file']);

        $commandTester->assertCommandIsSuccessful();
    }

    public function testMissingArgument()
    {
        $importer = $this->createMock(Importer::class);
        $importer->expects($this->never())->method('importFile');

        $command = $this->createCommand($importer);
        $commandTester = $this->createCommandTester(self::Name, $command);

        $this->expectException(RuntimeException::class);
        $commandTester->execute([]);
    }
}
