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
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\SharedEventManager;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Filter\FilterPluginManager;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\ModuleManager\Feature\ControllerPluginProviderInterface;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;
use Laminas\ModuleManager\Feature\ViewHelperProviderInterface;
use Laminas\ModuleManager\Listener\DefaultListenerAggregate;
use Laminas\ModuleManager\Listener\ListenerOptions;
use Laminas\ModuleManager\Listener\ServiceListener;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use Laminas\View\Helper\Doctype;
use Laminas\View\Helper\Url;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplatePathStack;
use Model\Client\Client;
use Nada\Database\AbstractDatabase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ServiceManagerFactory
{
    private const Modules = [
        'Laminas\Filter',
        'Laminas\Form',
        'Laminas\I18n',
        'Laminas\Router',
        'Laminas\Validator',
        'Console',
    ];

    public function __invoke(ContainerInterface $container): ServiceManager
    {
        $serviceManager = new ServiceManager();
        $serviceManager->configure([
            'aliases' => [
                'application' => 'Application',
                'Config' => 'config',
                'configuration' => 'config',
                'Configuration' => 'config',
                'EventManagerInterface' => EventManager::class,
                EventManagerInterface::class => 'EventManager',
                ModuleManager::class => 'ModuleManager',
                PluginManager::class => 'ControllerPluginManager',
                'request' => 'Request',
                'response' => 'Response',
                ServiceListener::class => 'ServiceListener',
                SharedEventManager::class => 'SharedEventManager',
                'SharedEventManagerInterface' => 'SharedEventManager',
                SharedEventManagerInterface::class => 'SharedEventManager',
            ],
            'factories' => [
                'Application' => fn(ContainerInterface $container) => new ApplicationService(
                    $container,
                    $container->get('Request'),
                    $container->get('Response'),
                    $container->get('Router'),
                ),
                'config' => fn(ContainerInterface $container) => $container->get('ModuleManager')
                    ->loadModules()->getEvent()->getParam('configListener')->getMergedConfig(false),
                'ControllerPluginManager' => fn(ContainerInterface $container) => new PluginManager($container),
                'EventManager' => fn(ContainerInterface $container) => new EventManager(
                    $container->get('SharedEventManager')
                ),
                'ModuleManager' => $this->moduleManagerFactory(...),
                'ServiceListener' => fn(ContainerInterface $container) => new ServiceListener($container),
                'SharedEventManager' => static fn() => new SharedEventManager(),
            ],
            'services' => [
                // standard Laminas services, partially tweaked
                'ApplicationConfig' => ['modules' => self::Modules],
                'Request' => new Request(false),
                'Response' => new Response(),
                ServiceManager::class => $serviceManager,
                'ViewResolver' => new TemplatePathStack([
                    'script_paths' => [InstalledVersions::getRootPackage()['install_path'] . 'module/Console/views'],
                    'default_suffix' => 'php',
                ]),
                // application-specific services defined in main container
                AbstractDatabase::class => $container->get(AbstractDatabase::class),
                Adapter::class => $container->get(Adapter::class),
                AppConfig::class => $container->get(AppConfig::class),
                AssetUrlFunction::class => $container->get(AssetUrlFunction::class),
                Client::class => $container->get(Client::class),
                ClockInterface::class => $container->get(ClockInterface::class),
                ContainerInterface::class => $serviceManager,
                Group::class => $container->get(Group::class),
                I18nTranslatorInterface::class => $container->get(I18nTranslator::class),
                LoggerInterface::class => $container->get(LoggerInterface::class),
                PathForRouteFunction::class => $container->get(PathForRouteFunction::class),
                RouteHelper::class => $container->get(RouteHelper::class),
                TemplateEngine::class => $container->get(TemplateEngine::class),
                TranslatorInterface::class => $container->get(TranslatorInterface::class),
            ],
            'shared' => [
                'EventManager' => false,
            ],
        ]);

        // There is a mutual dependency between the ViewHelperManager and the
        // PhpRenderer services. $phpRenderer->setHelperPluginManager() will
        // inject itself into the ViewHelperManager. The ViewHelperManager must
        // not create any helpers before this happens. Otherwise the created
        // helper would have an invalid renderer. To guarantee correct setup
        // independent of order, do not define factories, but create the
        // instances directly here.
        $viewHelperManager = new HelperPluginManager($serviceManager);
        $serviceManager->setService('ViewHelperManager', $viewHelperManager);

        $phpRenderer = new PhpRenderer();
        $phpRenderer->setHelperPluginManager($viewHelperManager);
        $phpRenderer->setResolver($serviceManager->get('ViewResolver'));
        $serviceManager->setService(PhpRenderer::class, $phpRenderer);

        // The Url helper is not registered by default.
        // @codeCoverageIgnoreStart
        $urlFactory = function () use ($serviceManager): Url {
            $helper = new Url();
            $helper->setRouter($serviceManager->get('HttpRouter'));
            $match = $serviceManager->get('Application')->getMvcEvent()->getRouteMatch();
            if ($match instanceof RouteMatch) {
                $helper->setRouteMatch($match);
            }
            return $helper;
        };
        $viewHelperManager->setFactory(Url::class, $urlFactory);
        $viewHelperManager->setFactory('laminasviewhelperurl', $urlFactory);

        // The Doctype helper is not registered by default.
        $doctypeFactory = function (): Doctype {
            $helper = new Doctype();
            $helper->setDoctype(Doctype::HTML5);
            return $helper;
        };
        $viewHelperManager->setFactory(Doctype::class, $doctypeFactory);
        $viewHelperManager->setFactory('laminasviewhelperdoctype', $doctypeFactory);
        // @codeCoverageIgnoreEnd

        // Define services from modules.
        $serviceManager->get('ModuleManager')->loadModules();

        // Abstract factories are invoked in the same order in which they get
        // added. The abstract DI factory should act as a fallback only. It
        // cannot be added via config because other modules might add another
        // abstract factory after the DI factory.
        $serviceManager->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(FilterPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(PluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(ValidatorPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $viewHelperManager->addAbstractFactory(AutowireFactory::class);

        return $serviceManager;
    }

    private function moduleManagerFactory(ContainerInterface $container): ModuleManager
    {
        /** @var EventManager */
        $eventManager = $container->get('EventManager');

        $defaultListeners = new DefaultListenerAggregate(new ListenerOptions([
            'module_paths' => [InstalledVersions::getRootPackage()['install_path'] . 'module'],
        ]));
        $defaultListeners->attach($eventManager);

        /** @var ServiceListener */
        $serviceListener  = $container->get('ServiceListener');
        /** @psalm-suppress InvalidCast,InvalidArgument (implementation does not match interface signature) */
        $serviceListener->addServiceManager(
            $container,
            'service_manager',
            ServiceProviderInterface::class,
            'getServiceConfig'
        );
        $serviceListener->addServiceManager(
            'ControllerPluginManager',
            'controller_plugins',
            ControllerPluginProviderInterface::class,
            'getControllerPluginConfig'
        );
        $serviceListener->addServiceManager(
            'ViewHelperManager',
            'view_helpers',
            ViewHelperProviderInterface::class,
            'getViewHelperConfig'
        );
        $serviceListener->attach($eventManager);

        $moduleEvent = new ModuleEvent();
        $moduleEvent->setParam('ServiceManager', $container);

        $moduleManager = new ModuleManager(self::Modules, $eventManager);
        $moduleManager->setEvent($moduleEvent);

        return $moduleManager;
    }
}
