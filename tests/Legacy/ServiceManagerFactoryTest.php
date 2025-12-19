<?php

namespace Braintacle\Test\Legacy;

use Braintacle\AppConfig;
use Braintacle\Group\Group;
use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\I18nTranslator;
use Braintacle\Legacy\ServiceManagerFactory;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use DI\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Translator\TranslatorInterface;
use Model\Client\Client;
use Nada\Database\AbstractDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

#[CoversClass(ServiceManagerFactory::class)]
final class ServiceManagerFactoryTest extends TestCase
{
    public function testServiceFromMainContainer()
    {
        $services = [
            AbstractDatabase::class => $this->createStub(AbstractDatabase::class),
            Adapter::class => $this->createStub(Adapter::class),
            AppConfig::class => $this->createStub(AppConfig::class),
            AssetUrlFunction::class => $this->createStub(AssetUrlFunction::class),
            Client::class => $this->createStub(Client::class),
            ClockInterface::class => $this->createStub(ClockInterface::class),
            Group::class => $this->createStub(Group::class),
            I18nTranslator::class => $this->createStub(I18nTranslator::class),
            LoggerInterface::class => $this->createStub(LoggerInterface::class),
            PathForRouteFunction::class => $this->createStub(PathForRouteFunction::class),
            RouteHelper::class => $this->createStub(RouteHelper::class),
            TemplateEngine::class => $this->createStub(TemplateEngine::class),
            TranslatorInterface::class => $this->createStub(TranslatorInterface::class),
        ];
        $container = new Container($services);
        $factory = new ServiceManagerFactory();
        $serviceManager = $factory($container);

        $this->assertSame($services[AbstractDatabase::class], $serviceManager->get(AbstractDatabase::class));
        $this->assertSame($services[Adapter::class], $serviceManager->get(Adapter::class));
        $this->assertSame($services[AppConfig::class], $serviceManager->get(AppConfig::class));
        $this->assertSame($services[AssetUrlFunction::class], $serviceManager->get(AssetUrlFunction::class));
        $this->assertSame($services[Client::class], $serviceManager->get(Client::class));
        $this->assertSame($services[ClockInterface::class], $serviceManager->get(ClockInterface::class));
        $this->assertSame($services[Group::class], $serviceManager->get(Group::class));
        $this->assertSame($services[I18nTranslator::class], $serviceManager->get(I18nTranslatorInterface::class));
        $this->assertSame($services[LoggerInterface::class], $serviceManager->get(LoggerInterface::class));
        $this->assertSame($services[PathForRouteFunction::class], $serviceManager->get(PathForRouteFunction::class));
        $this->assertSame($services[RouteHelper::class], $serviceManager->get(RouteHelper::class));
        $this->assertSame($services[TemplateEngine::class], $serviceManager->get(TemplateEngine::class));
        $this->assertSame($services[TranslatorInterface::class], $serviceManager->get(TranslatorInterface::class));
        $this->assertSame($serviceManager, $serviceManager->get(ContainerInterface::class));
    }
}
