<?php

namespace Braintacle\Cli\Command;

use Library\FileObject;
use Protocol\Filter\InventoryDecode;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Decode a compressed inventory file.
 */
#[AsCommand(
    name: 'decode',
    description: 'Decode a compressed inventory file as created by agents',
)]
final class DecodeCommand
{
    public function __construct(
        private InventoryDecode $inventoryDecodeFilter,
        private Filesystem $filesystem,
    ) {}

    public function __invoke(
        OutputInterface $output,
        #[Argument(description: 'compressed input file', name: 'input file')]
        string $inputFile,
        #[Argument(description: 'XML output file (default: print to STDOUT)', name: 'output file')]
        ?string $outputFile = null,
    ): int {
        $content = $this->inventoryDecodeFilter->filter($this->filesystem->readFile($inputFile));
        if ($outputFile) {
            $this->filesystem->dumpFile($outputFile, $content);
        } else {
            $output->write($content);
        }

        return Command::SUCCESS;
    }
}
