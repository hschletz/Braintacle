<?php

namespace Braintacle\Test\Legacy\Plugin;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\Plugin\ControllerPluginTrait;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Legacy\Plugin\Params;
use Braintacle\Legacy\Plugin\PluginManager;
use Braintacle\Legacy\Plugin\RedirectToRoute;
use Console\Mvc\Controller\Plugin\GetOrder;
use Console\Mvc\Controller\Plugin\PrintForm;
use Console\Mvc\Controller\Plugin\Translate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[CoversClass(PluginManager::class)]
#[UsesClass(ControllerPluginTrait::class)]
final class PluginManagerTest extends TestCase
{
    public static function getProvider()
    {
        return [
            ['_', Translate::class],
            ['flashMessenger', FlashMessenger::class],
            ['getOrder', GetOrder::class],
            ['params', Params::class],
            ['printForm', PrintForm::class],
            ['redirectToRoute', RedirectToRoute::class],
        ];
    }

    #[DataProvider('getProvider')]
    public function testGet(string $name, string $class)
    {
        $controller = $this->createStub(Controller::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with($class)->willReturn(new class {
            use ControllerPluginTrait;

            // class must be callable
            public function __invoke() {}
        });

        $pluginManager = new PluginManager($container);
        $pluginManager->setController($controller);

        /**
         * @psalm-suppress InvalidMethodCall
         * @phpstan-ignore method.nonObject
         */
        $this->assertSame($controller, $pluginManager->get($name)->getController());
    }
}
