<?php

namespace Braintacle\Cli;

use Monolog\Level;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Map Symfony verbosity levels to Monolog log levels.
 *
 * https://symfony.com/doc/7.4/logging/monolog_console.html lacks a mapping for
 * VERBOSITY_SILENT. This gets mapped to Emergency to provide a valid value.
 */
final class LogLevelMapper
{
    public function map(OutputInterface $output)
    {
        return match ($output->getVerbosity()) {
            OutputInterface::VERBOSITY_SILENT => Level::Emergency,
            OutputInterface::VERBOSITY_QUIET => Level::Error,
            OutputInterface::VERBOSITY_NORMAL => Level::Warning,
            OutputInterface::VERBOSITY_VERBOSE => Level::Notice,
            OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
            OutputInterface::VERBOSITY_DEBUG => Level::Debug,
        };
    }
}
