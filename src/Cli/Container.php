<?php

namespace Braintacle\Cli;

use Braintacle\Cli\Command\BuildCommand;
use Braintacle\Cli\Command\DatabaseCommand;
use Braintacle\Cli\Command\DecodeCommand;
use Braintacle\Cli\Command\ExportCommand;
use Braintacle\Cli\Command\ImportCommand;
use Braintacle\Container as BaseContainer;
use DI\Factory\RequestedEntry;
use LogicException;

use function DI\autowire;

/**
 * Container for CLI.
 */
final class Container extends BaseContainer
{
    public function __construct()
    {
        parent::__construct();

        // Instantiate CLI commands lazily if possible to prevent early
        // AppConfig initialization before the --config option has been parsed.
        // For PHP before 8.4 this would require an additional dependency which
        // is not included in composer.json. For those systems, the --config
        // option is not available. The BRAINTACLE_CONFIG environment variable
        // can be used on all systems.
        if (ToolsApplication::supportsConfigOption()) {
            $this->set(BuildCommand::class, autowire()->lazy());
            $this->set(DatabaseCommand::class, autowire()->lazy());
            $this->set(DecodeCommand::class, autowire()->lazy());
            $this->set(ExportCommand::class, autowire()->lazy());
            $this->set(ImportCommand::class, autowire()->lazy());

            // Fallback: fail if new commands get added without a lazy service definition above.
            $this->set('Braintacle\Cli\Command\*', function (RequestedEntry $requestedEntry) {
                throw new LogicException("Service {$requestedEntry->getName()} must be explicitly defined as lazy");
            });
        }
    }
}
