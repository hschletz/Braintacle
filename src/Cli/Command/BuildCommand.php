<?php

namespace Braintacle\Cli\Command;

use Braintacle\Package\Action;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\SourceFileFactory;
use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Model\Config;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Build a package.
 */
#[AsCommand(
    name: 'build',
    description: 'Build a package',
)]
class BuildCommand
{
    public function __construct(
        private Config $config,
        private SourceFileFactory $sourceFileFactory,
        private Builder $builder,
    ) {}

    public function __invoke(
        SymfonyStyle $symfonyStyle,
        #[Argument(description: 'package name')] string $name,
        #[Argument(description: 'file with package content')] string $file,
    ): int {
        $package = new Package();
        $package->name = $name;
        $package->comment = null;
        $package->platform = Platform::from($this->config->defaultPlatform);
        $package->action = Action::from($this->config->defaultAction);
        $package->actionParam = $this->config->defaultActionParam;
        $package->priority = $this->config->defaultPackagePriority;
        $package->maxFragmentSize = $this->config->defaultMaxFragmentSize;
        $package->warn = $this->config->defaultWarn;
        $package->warnMessage = $this->config->defaultWarnMessage;
        $package->warnCountdown = $this->config->defaultWarnCountdown;
        $package->warnAllowAbort = $this->config->defaultWarnAllowAbort;
        $package->warnAllowDelay = $this->config->defaultWarnAllowDelay;
        $package->postInstMessage = $this->config->defaultPostInstMessage;

        $sourceFile = $this->sourceFileFactory->fromPath($file);

        $this->builder->build($package, $sourceFile, false);
        $symfonyStyle->getErrorStyle()->success('Package successfully built.');

        return Command::SUCCESS;
    }
}
