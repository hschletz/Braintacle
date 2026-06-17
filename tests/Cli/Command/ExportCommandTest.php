<?php

namespace Braintacle\Test\Cli\Command;

use Braintacle\Cli\Command\ExportCommand;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Protocol\Message\InventoryRequest;
use RuntimeException;
use Symfony\Component\Console\Exception\RuntimeException as ConsoleRuntimeException;

#[CoversClass(ExportCommand::class)]
final class ExportCommandTest extends TestCase
{
    use CommandTesterTrait;

    private const Name = 'export';

    private string $displayErrors;
    private string $logErrors;

    #[Before]
    public function backupIniSettings(): void
    {
        $this->displayErrors = ini_get('display_errors');
        $this->logErrors = ini_get('log_errors');
    }

    #[After]
    public function restoreIniSettings(): void
    {
        ini_set('display_errors', $this->displayErrors);
        ini_set('log_errors', $this->logErrors);
    }

    private function createCommand(?ClientManager $clientManager = null): ExportCommand
    {
        return new ExportCommand($clientManager ?? $this->createStub(ClientManager::class));
    }

    public function testExportWithoutValidation()
    {
        $document1 = $this->createMock(InventoryRequest::class);
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('write')->with("/tmp/filename1");
        $document1->expects($this->never())->method('isValid');

        $document2 = $this->createMock(InventoryRequest::class);
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('write')->with("/tmp/filename2");
        $document2->expects($this->never())->method('isValid');

        $client1 = $this->createStub(Client::class);
        $client1->idString = 'client1';
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createStub(Client::class);
        $client2->idString = 'client2';
        $client2->method('toDomDocument')->willReturn($document2);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClients')->with(null, 'IdString')->willReturn([$client1, $client2]);

        $command = $this->createCommand($clientManager);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute(['directory' => '/tmp'], ['capture_stderr_separately' => true]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertEquals("Exporting client1\nExporting client2\n", $commandTester->getErrorOutput(true));
    }

    public function testExportWithValidation()
    {
        $document1 = $this->createMock(InventoryRequest::class);
        $document1->method('getFilename')->willReturn('filename1');
        $document1->expects($this->once())->method('write')->with("/tmp/filename1");
        $document1->expects($this->once())->method('isValid')->willReturn(true);

        $document2 = $this->createMock(InventoryRequest::class);
        $document2->method('getFilename')->willReturn('filename2');
        $document2->expects($this->once())->method('write')->with("/tmp/filename2");
        $document2->expects($this->once())->method('isValid')->willReturn(false);

        $document3 = $this->createMock(InventoryRequest::class);
        $document3->expects($this->never())->method('getFilename');
        $document3->expects($this->never())->method('write');
        $document3->expects($this->never())->method('isValid');

        $client1 = $this->createStub(Client::class);
        $client1->idString = 'client1';
        $client1->method('toDomDocument')->willReturn($document1);

        $client2 = $this->createStub(Client::class);
        $client2->idString = 'client2';
        $client2->method('toDomDocument')->willReturn($document2);

        $client3 = $this->createMock(Client::class);
        $client2->idString = 'client3';
        $client3->expects($this->never())->method('toDomDocument');

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClients')->with(null, 'IdString')->willReturn([$client1, $client2, $client3]);

        $command = $this->createCommand($clientManager);
        $commandTester = $this->createCommandTester(self::Name, $command);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Validation failed for client3');

        $commandTester->execute(['directory' => '/tmp', '--validate' => true]);
    }

    #[TestWith([[]])]
    #[TestWith([['--validate' => true]])]
    public function testMissingArgument(array $input)
    {
        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->never())->method('getClients');


        $command = $this->createCommand($clientManager);
        $commandTester = $this->createCommandTester(self::Name, $command);

        $this->expectException(ConsoleRuntimeException::class);
        $commandTester->execute($input);
    }
}
