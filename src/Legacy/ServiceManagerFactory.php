<?php

namespace Braintacle\Legacy;

use Braintacle\AppConfig;
use Braintacle\Group\Group;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\Function\AssetUrlFunction;
use Braintacle\Template\Function\PathForRouteFunction;
use Braintacle\Template\TemplateEngine;
use Composer\InstalledVersions;
use Laminas\Db\Adapter\Adapter;
use Laminas\Di\Container\ServiceManager\AutowireFactory;
use Laminas\Filter\FilterPluginManager;
use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\Mvc\Application;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use Model\Client\Client;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ServiceManagerFactory
{
    public function __invoke(ContainerInterface $container): ServiceManager
    {
        $application = Application::init([
            'modules' => [
                'Laminas\Filter',
                'Laminas\Form',
                'Laminas\I18n',
                'Laminas\Mvc\I18n',
                'Laminas\Mvc\Plugin\FlashMessenger',
                'Laminas\Router',
                'Laminas\Validator',
                'Console',
            ],
            'module_listener_options' => [
                'module_paths' => [InstalledVersions::getRootPackage()['install_path'] . 'module'],
            ],
        ]);
        $serviceManager = $application->getServiceManager();

        // Abstract factories are invoked in the same order in which they get
        // added. The abstract DI factory should act as a fallback only. It
        // cannot be added via config because other modules might add another
        // abstract factory after the DI factory.
        $serviceManager->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(FilterPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(ValidatorPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get('ViewHelperManager')->addAbstractFactory(AutowireFactory::class);

        // Inject Services which are not provided by module's ServiceManager configuration.
        $serviceManager->setService(AbstractDatabase::class, $container->get(AbstractDatabase::class));
        $serviceManager->setService(Adapter::class, $container->get(Adapter::class));
        $serviceManager->setService(AppConfig::class, $container->get(AppConfig::class));
        $serviceManager->setService(AssetUrlFunction::class, $container->get(AssetUrlFunction::class));
        $serviceManager->setService(Client::class, $container->get(Client::class));
        $serviceManager->setService(ClockInterface::class, $container->get(ClockInterface::class));
        $serviceManager->setService(ContainerInterface::class, $serviceManager);
        $serviceManager->setService(Group::class, $container->get(Group::class));
        $serviceManager->setService(I18nTranslatorInterface::class, $container->get(I18nTranslator::class));
        $serviceManager->setService(LoggerInterface::class, $container->get(LoggerInterface::class));
        $serviceManager->setService(PathForRouteFunction::class, $container->get(PathForRouteFunction::class));
        $serviceManager->setService(RouteHelper::class, $container->get(RouteHelper::class));
        $serviceManager->setService(TemplateEngine::class, $container->get(TemplateEngine::class));
        $serviceManager->setService(TranslatorInterface::class, $container->get(TranslatorInterface::class));

        return $serviceManager;
    }
}
