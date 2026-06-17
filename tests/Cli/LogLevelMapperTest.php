<?php

namespace Braintacle\Test\Cli;

use Braintacle\Cli\LogLevelMapper;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(LogLevelMapper::class)]
final class LogLevelMapperTest extends TestCase
{
    #[TestWith([OutputInterface::VERBOSITY_SILENT, Level::Emergency])]
    #[TestWith([OutputInterface::VERBOSITY_QUIET, Level::Error])]
    #[TestWith([OutputInterface::VERBOSITY_NORMAL, Level::Warning])]
    #[TestWith([OutputInterface::VERBOSITY_VERBOSE, Level::Notice])]
    #[TestWith([OutputInterface::VERBOSITY_VERY_VERBOSE, Level::Info])]
    #[TestWith([OutputInterface::VERBOSITY_DEBUG, Level::Debug])]
    public function testMap(int $verbosity, Level $level)
    {
        $output = $this->createStub(OutputInterface::class);
        $output->method('getVerbosity')->willReturn($verbosity);

        $mapper = new LogLevelMapper();
        $this->assertEquals($level, $mapper->map($output));
    }
}
