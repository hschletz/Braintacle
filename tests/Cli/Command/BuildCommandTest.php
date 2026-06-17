<?php

namespace Braintacle\Test\Cli\Command;

use Braintacle\Cli\Command\BuildCommand;
use Braintacle\Package\Action;
use Braintacle\Package\Build\Builder;
use Braintacle\Package\Build\SourceFile;
use Braintacle\Package\Build\SourceFileFactory;
use Braintacle\Package\Package;
use Braintacle\Package\Platform;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;

#[CoversClass(BuildCommand::class)]
final class BuildCommandTest extends TestCase
{
    use CommandTesterTrait;

    private const Name = 'build';

    private function createCommand(
        ?Config $config = null,
        ?SourceFileFactory $sourceFileFactory = null,
        ?Builder $builder = null,
    ): BuildCommand {
        return new BuildCommand(
            $config ?? $this->createStub(Config::class),
            $sourceFileFactory ?? $this->createStub(SourceFileFactory::class),
            $builder ?? $this->createStub(Builder::class),
        );
    }

    public function testBuild()
    {
        $config = $this->createStub(Config::class);
        $config->method('__get')->willReturnMap([
            ['defaultPlatform', 'linux'],
            ['defaultAction', 'execute'],
            ['defaultActionParam', 'actionParam'],
            ['defaultPackagePriority', '8'],
            ['defaultMaxFragmentSize', '42'],
            ['defaultWarn', '1'],
            ['defaultWarnMessage', 'warnMessage'],
            ['defaultWarnCountdown', '60'],
            ['defaultWarnAllowAbort', '0'],
            ['defaultWarnAllowDelay', '1'],
            ['defaultPostInstMessage', 'postInstMessage'],
        ]);

        $sourceFile = $this->createStub(SourceFile::class);
        $sourceFileFactory = $this->createMock(SourceFileFactory::class);
        $sourceFileFactory->method('fromPath')->with('path/fileName')->willReturn($sourceFile);

        $builder = $this->createMock(Builder::class);
        $builder->expects($this->once())->method('build')->with(
            $this->callback(function (Package $package) {
                $this->assertEquals(
                    [
                        'name' => 'packageName',
                        'comment' => null,
                        'priority' => 8,
                        'platform' => Platform::Linux,
                        'action' => Action::Execute,
                        'actionParam' => 'actionParam',
                        'warn' => true,
                        'warnMessage' => 'warnMessage',
                        'warnCountdown' => 60,
                        'warnAllowAbort' => false,
                        'warnAllowDelay' => true,
                        'postInstMessage' => 'postInstMessage',
                        'maxFragmentSize' => 42,
                    ],
                    (array) $package,
                );
                return true;
            }),
            $sourceFile,
            false
        );

        $command = $this->createCommand($config, $sourceFileFactory, $builder);

        $commandTester = $this->createCommandTester(self::Name, $command);
        $commandTester->execute(
            [
                'name' => 'packageName',
                'file' => 'path/fileName',
            ],
            ['capture_stderr_separately' => true]
        );

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Package successfully built.', $commandTester->getErrorOutput());
    }

    #[TestWith([['name' => 'packageName']])]
    #[TestWith([['file' => 'path/fileName']])]
    public function testMissingArgument(array $arguments)
    {
        $builder = $this->createMock(Builder::class);
        $builder->expects($this->never())->method('build');

        $command = $this->createCommand(builder: $builder);
        $commandTester = $this->createCommandTester(self::Name, $command);

        $this->expectException(RuntimeException::class);
        $commandTester->execute($arguments);
    }
}
