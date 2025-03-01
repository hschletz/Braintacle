<?php

namespace Braintacle\Database;

use Composer\InstalledVersions;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\JsonFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manage database migrations.
 */
final class Migrations
{
    public function __construct(private Connection $connection, private Application $application) {}

    public function migrate(OutputInterface $output, string $version): void
    {
        // doctrine/migrations does not provide distinct methods for its
        // operations. Creating and wrapping a Symfony console applications is
        // the documented way to integrate into applications.

        $configFile = InstalledVersions::getRootPackage()['install_path'] . '/migrations.json';
        $config = new JsonFile($configFile);
        $dependencyFactory = DependencyFactory::fromConnection($config, new ExistingConnection($this->connection));

        $this->application->add(new MigrateCommand($dependencyFactory));

        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            'version' => $version,
        ]);
        $input->setInteractive(false);

        $result = $this->application->doRun($input, $output);
        if ($result != Command::SUCCESS) {
            throw new RuntimeException('Migrations failed with status ' . (string) $result);
        }
    }
}
