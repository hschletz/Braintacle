<?php

namespace Braintacle\Cli;

use Braintacle\AppConfig;
use Override;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Braintacle command line tools collection.
 *
 * The global option --config (to use an alternative config file) requires some
 * quirks to work. The config file must be applied to the AppConfig instance as
 * early as possible to prevent instantiation of services (i.e. the database
 * connection) with incorrect values from the default config file. Symfony's
 * command line parser has no API to be used standalone.
 *
 * To prevent premature command instantiation, the container injects lazy
 * proxies for the command classes. An event listener evaluates the option
 * (which is not available before this stage) and sets the config file before
 * the real instance gets initialized.
 */
final class ToolsApplication extends Application
{
    public function __construct(
        private AppConfig $appConfig,
        Command\BuildCommand $buildCommand,
        Command\DatabaseCommand $databaseCommand,
        Command\DecodeCommand $decodeCommand,
        Command\ExportCommand $exportCommand,
        Command\ImportCommand $importCommand,
    ) {
        parent::__construct("Braintacle command line tool");

        $this->addCommand($buildCommand);
        $this->addCommand($databaseCommand);
        $this->addCommand($decodeCommand);
        $this->addCommand($exportCommand);
        $this->addCommand($importCommand);

        if (self::supportsConfigOption()) {
            $eventDispatcher = new EventDispatcher();
            $eventDispatcher->addListener(ConsoleEvents::COMMAND, $this->handleConfig(...));
            $this->setDispatcher($eventDispatcher);
        }
    }

    private function handleConfig(ConsoleCommandEvent $event): void
    {
        $config = $event->getInput()->getOption('config');
        if ($config) {
            $this->appConfig->setFile($config);
        }
    }

    #[Override]
    protected function getDefaultInputDefinition(): InputDefinition
    {
        // Define additional global option.
        $definition = parent::getDefaultInputDefinition();

        if (self::supportsConfigOption()) {
            $definition->addOption(
                new InputOption(
                    "config",
                    "c",
                    InputOption::VALUE_REQUIRED,
                    "Alternative config file",
                ),
            );
        }

        return $definition;
    }

    public static function supportsConfigOption(): bool
    {
        return version_compare(PHP_VERSION, '8.4', '>=');
    }
}
