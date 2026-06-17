<?php

namespace Braintacle\Test\Cli\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Command testing helper.
 */
trait CommandTesterTrait
{
    private function createCommandTester(string $name, callable $command): CommandTester
    {
        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($application->get($name));
    }
}
