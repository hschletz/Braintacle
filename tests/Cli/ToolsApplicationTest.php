<?php

namespace Braintacle\Test\Cli;

use Braintacle\AppConfig;
use Braintacle\Cli\Command\BuildCommand;
use Braintacle\Cli\Command\DatabaseCommand;
use Braintacle\Cli\Command\DecodeCommand;
use Braintacle\Cli\Command\ExportCommand;
use Braintacle\Cli\Command\ImportCommand;
use Braintacle\Cli\ToolsApplication;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Tester\ApplicationTester;

#[CoversClass(ToolsApplication::class)]
#[UsesClass(BuildCommand::class)]
final class ToolsApplicationTest extends TestCase
{
    private function createApplication(
        ?AppConfig $appConfig = null,
        ?BuildCommand $buildCommand = null,
        ?DatabaseCommand $databaseCommand = null,
        ?DecodeCommand $decodeCommand = null,
        ?ExportCommand $exportCommand = null,
        ?ImportCommand $importCommand = null,
    ): ToolsApplication {
        $application = new ToolsApplication(
            $appConfig ?? $this->createStub(AppConfig::class),
            $buildCommand ?? (new ReflectionClass(BuildCommand::class))->newLazyGhost($this->initializer(...)),
            $databaseCommand ?? (new ReflectionClass(DatabaseCommand::class))->newLazyGhost($this->initializer(...)),
            $decodeCommand ?? (new ReflectionClass(DecodeCommand::class))->newLazyGhost($this->initializer(...)),
            $exportCommand ?? (new ReflectionClass(ExportCommand::class))->newLazyGhost($this->initializer(...)),
            $importCommand ?? (new ReflectionClass(ImportCommand::class))->newLazyGhost($this->initializer(...)),
        );
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);

        return $application;
    }

    private function initializer(): void
    {
        $this->fail('Command constructor should not have been invoked');
    }

    #[TestWith(['build'])]
    #[TestWith(['database'])]
    #[TestWith(['decode'])]
    #[TestWith(['export'])]
    #[TestWith(['import'])]
    #[DoesNotPerformAssertions]
    public function testCommandIsAvailable(string $command)
    {
        $application = $this->createApplication();
        $application->get($command);
    }

    public function testLazyCommandsAreNotInitialized()
    {
        $applicationTester = new ApplicationTester($this->createApplication());
        $applicationTester->run(['command' => 'list']); // Should not cause initialization of command instances
        $applicationTester->assertCommandIsSuccessful();
    }

    public function testNoConfigOption()
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->never())->method('setFile');

        $applicationTester = new ApplicationTester($this->createApplication(appConfig: $appConfig));
        $applicationTester->run(['command' => 'list']);
    }

    #[TestWith(['--config'])]
    #[TestWith(['-c'])]
    #[RequiresPhp('>= 8.4')]
    public function testConfigOption(string $option)
    {
        $appConfig = $this->createMock(AppConfig::class);
        $appConfig->expects($this->once())->method('setFile')->with('config file');

        $applicationTester = new ApplicationTester($this->createApplication(appConfig: $appConfig));
        $applicationTester->run(['command' => 'list', $option => 'config file']);
    }

    #[RequiresPhp('>= 8.4')]
    public function testConfigOptionSupported()
    {
        $this->assertTrue(ToolsApplication::supportsConfigOption());
    }
}
