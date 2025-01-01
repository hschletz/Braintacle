<?php

namespace Braintacle\Test;

use Braintacle\Container;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Session\Validator\Csrf;
use Model\Operator\AuthenticationService;
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
        $container = new Container();
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertInstanceOf(AuthenticationService::class, $container->get(AuthenticationServiceInterface::class));
        $this->assertInstanceOf(ResponseInterface::class, $container->get(ResponseInterface::class));
        $this->assertInstanceOf(StorageInterface::class, $container->get(StorageInterface::class));

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
