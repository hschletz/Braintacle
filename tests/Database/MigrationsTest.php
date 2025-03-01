<?php

namespace Braintacle\Test\Database;

use Braintacle\Database\Migrations;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class MigrationsTest extends TestCase
{
    public function testMigrateSetup()
    {
        $connection = $this->createStub(Connection::class);
        $output = $this->createStub(OutputInterface::class);

        $application = $this->createPartialMock(Application::class, ['doRun']);
        $application->expects($this->once())->method('doRun')->with(
            $this->callback(
                function (InputInterface $input): bool {
                    $this->assertEquals("'migrations:migrate' migration_version", (string) $input);
                    $this->assertFalse($input->isInteractive());

                    return true;
                }
            ),
            $this->identicalTo($output),
        )->willReturn(Command::SUCCESS);

        $version = 'migration_version';

        $migrations = new Migrations($connection, $application);
        $migrations->migrate($output, $version);

        $this->assertInstanceOf(MigrateCommand::class, $application->get('migrations:migrate'));
    }

    public function testMigrateError()
    {
        $connection = $this->createStub(Connection::class);
        $output = $this->createStub(OutputInterface::class);

        $application = $this->createStub(Application::class);
        $application->method('doRun')->willReturn(Command::FAILURE);

        $version = 'migration_version';

        $migrations = new Migrations($connection, $application);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migrations failed with status ' . (string) Command::FAILURE);

        $migrations->migrate($output, $version);
    }
}
