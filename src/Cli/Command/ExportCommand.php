<?php

namespace Braintacle\Cli\Command;

use Model\Client\Client;
use Model\Client\ClientManager;
use RuntimeException;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;

/**
 * Export all clients.
 */
#[AsCommand(
    name: 'export',
    description: 'Export all clients as XML',
)]
final class ExportCommand
{
    public function __construct(private ClientManager $clientManager) {}

    public function __invoke(
        SymfonyStyle $symfonyStyle,
        #[Argument(description: 'output directory')] string $directory,
        #[Option(description: 'validate output documents, abort on error')] bool $validate = false,
    ): int {
        if ($validate) {
            ini_set('display_errors', true); // Print reason for validation failure
            ini_set('log_errors', 0); // Prevent duplicate message in case of validation failure
        }
        $errorStyle = $symfonyStyle->getErrorStyle();

        /** @var iterable<Client> */
        $clients = $this->clientManager->getClients(null, 'IdString');
        foreach ($clients as $client) {
            $id = $client->idString;
            $errorStyle->writeln("<info>Exporting $id</info>");
            $document = $client->toDomDocument();
            $document->write(Path::join($directory, $document->getFilename()));
            if ($validate && !$document->isValid()) {
                throw new RuntimeException('Validation failed for ' . $id);
            }
        }

        return Command::SUCCESS;
    }
}
