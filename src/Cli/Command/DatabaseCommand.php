<?php

namespace Braintacle\Cli\Command;

use Braintacle\Cli\LogLevelMapper;
use Braintacle\Database\Migrations;
use Database\SchemaManager;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manage database schema.
 */
#[AsCommand(
    name: 'database',
    description: 'Update the database',
)]
final class DatabaseCommand
{
    public function __construct(
        private Migrations $migrations,
        private SchemaManager $schemaManager,
        private LoggerInterface $logger,
        private LogLevelMapper $logLevelMapper,
    ) {}

    public function __invoke(
        OutputInterface $output,
        #[Argument(description: 'Set Database to given migration version (version or latest|prev|next|first)')]
        string $version = 'latest',
        #[Option('Drop obsolete columns (default: just warn)', shortcut: 'p')]
        bool $prune = false,
    ): int {
        $this->migrations->migrate($output, $version);

        // Assume logger as set up during container initialization.
        assert($this->logger instanceof Logger);
        $handler = new StreamHandler('php://stderr', $this->logLevelMapper->map($output));
        $handler->setFormatter(new LineFormatter("[%level_name%] %message%\n"));
        $this->logger->pushHandler($handler);

        $this->schemaManager->updateAll($prune);

        return Command::SUCCESS;
    }
}
