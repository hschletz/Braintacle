<?php

namespace Braintacle\Cli\Command;

use Braintacle\Client\Import\Importer;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

/**
 * Import client from XML file.
 */
#[AsCommand(
    name: 'import',
    description: 'Import clients from compressed or uncompressed XML files',
)]
final class ImportCommand
{
    public function __construct(private Importer $importer) {}

    public function __invoke(#[Argument(description: 'File to import')] string $filename): int
    {
        $this->importer->importFile($filename);

        return Command::SUCCESS;
    }
}
