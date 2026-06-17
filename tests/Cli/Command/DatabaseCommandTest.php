<?php

namespace Braintacle\Test\Cli\Command;

use Braintacle\Cli\Command\DatabaseCommand;
use Braintacle\Cli\LogLevelMapper;
use Braintacle\Database\Migrations;
use Database\SchemaManager;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(DatabaseCommand::class)]
final class DatabaseCommandTest extends TestCase
{
    use CommandTesterTrait;

    private const Name = 'database';

    private function createCommand(
        ?Migrations $migrations = null,
        ?SchemaManager $schemaManager = null,
        ?Logger $logger = null,
        ?LogLevelMapper $logLevelMapper = null,
    ): DatabaseCommand {
        if (!$logLevelMapper) {
            $logLevelMapper = $this->createStub(LogLevelMapper::class);
            $logLevelMapper->method('map')->willReturn(Level::Emergency); // should never occur and generate output
        }

        return new DatabaseCommand(
            $migrations ?? $this->createStub(Migrations::class),
            $schemaManager ?? $this->createStub(SchemaManager::class),
            $logger ?? $this->createStub(Logger::class),
            $logLevelMapper,
        );
    }

    #[TestWith([[], 'latest'])]
    #[TestWith([['version' => '_version'], '_version'])]
    public function testVersionArgument(array $input, string $version)
    {
        $migrations = $this->createMock(Migrations::class);
        $migrations->expects($this->once())->method('migrate')->with(
            $this->isInstanceOf(OutputInterface::class),
            $version,
        );

        $command = $this->createCommand(migrations: $migrations);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute($input);

        $commandTester->assertCommandIsSuccessful();
    }

    #[TestWith([[], false])]
    #[TestWith([['-p' => true], true])]
    #[TestWith([['--prune' => true], true])]
    public function testPruneOption(array $input, bool $prune)
    {
        $schemaManager = $this->createMock(SchemaManager::class);
        $schemaManager->expects($this->once())->method('updateAll')->with($prune);

        $command = $this->createCommand(schemaManager: $schemaManager);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute($input);

        $commandTester->assertCommandIsSuccessful();
    }

    #[TestWith([[], OutputInterface::VERBOSITY_NORMAL, Level::Warning])]
    #[TestWith([['verbosity' => OutputInterface::VERBOSITY_QUIET], OutputInterface::VERBOSITY_QUIET, Level::Error])]
    public function testLogger(array $options, int $verbosity, Level $level)
    {
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())->method('pushHandler')->with($this->callback(
            function (StreamHandler $handler) use ($level): bool {
                $this->assertEquals('php://stderr', $handler->getUrl());
                $this->assertEquals($level, $handler->getLevel());

                return true;
            }
        ));

        $logLevelMapper = $this->createMock(LogLevelMapper::class);
        $logLevelMapper
            ->method('map')
            ->with($this->callback(function (OutputInterface $output) use ($verbosity): bool {
                $this->assertEquals($verbosity, $output->getVerbosity());

                return true;
            }))
            ->willReturn($level);

        $command = $this->createCommand(logger: $logger, logLevelMapper: $logLevelMapper);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute([], $options);

        $commandTester->assertCommandIsSuccessful();
    }
}
