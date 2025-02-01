<?php

namespace Braintacle\Test;

use Braintacle\Container;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Validator\Csrf;
use Model\Operator\AuthenticationService;
use Model\Package\Storage\Direct as DirectStorage;
use Model\Package\Storage\StorageInterface;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

// Factories are tested separately where feasible.
class ContainerTest extends TestCase
{
    public function testInterfaceAliases()
    {
        // These classes have an implicit dependency on the config file, which
        // may not exist in a test environment. Provide stubs to prevent
        // failures.
        $authenticationService = $this->createStub(AuthenticationService::class);
        $storage = $this->createStub(DirectStorage::class);

        $container = new Container();
        $container->set(AuthenticationService::class, $authenticationService);
        $container->set(DirectStorage::class, $storage);

        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertSame($authenticationService, $container->get(AuthenticationServiceInterface::class));
        $this->assertSame($storage, $container->get(StorageInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $container->get(ResponseInterface::class));

        // Test for explicit implementation because Logger setup is done by consuming code
        $this->assertInstanceOf(Logger::class, $container->get(LoggerInterface::class));
    }

    public function testCsrfValidator()
    {
        $container = new Container();
        $csrfValidator = $container->get(Csrf::class);
        $this->assertInstanceOf(Csrf::class, $csrfValidator);
        $this->assertNull($csrfValidator->getTimeout());
    }
}
