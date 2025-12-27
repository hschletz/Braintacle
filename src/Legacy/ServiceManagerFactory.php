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
use Laminas\Form\FormElementManager;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Http\Request as HttpRequest;
use Laminas\I18n\Translator\TranslatorInterface as I18nTranslatorInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\ValidatorPluginManager;
use Laminas\View\Helper\Doctype;
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
        'Library',
        'Model',
        'Protocol',
        'Console',
    ];

    public function __invoke(ContainerInterface $container): ServiceManager
    {
        $serviceManager = new ServiceManager();

        $config = ['service_manager' => [
            'aliases' => [
                'application' => 'Application',
                'Config' => 'config',
                'configuration' => 'config',
                'Configuration' => 'config',
                'request' => 'Request',
                HttpRequest::class => 'Request',
                'response' => 'Response',
            ],
            'factories' => [
                'Application' => fn(ContainerInterface $container) => new ApplicationService(
                    $container,
                    $container->get('Request'),
                    $container->get('Response'),
                    $container->get('Router'),
                ),
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
        ]];
        foreach (self::Modules as $moduleName) {
            $moduleClass = $moduleName . '\Module';
            $module = new $moduleClass();
            $config = ArrayUtils::merge($config, $module->getConfig());
        };

        $serviceManager->configure($config['service_manager']);
        $serviceManager->setService('config', $config);

        /** @var FormElementManager */
        $formElementManager = $serviceManager->get(FormElementManager::class);
        $formElementManager->configure($config['form_elements']);

        // There is a mutual dependency between the ViewHelperManager and the
        // PhpRenderer services. $phpRenderer->setHelperPluginManager() will
        // inject itself into the ViewHelperManager. The ViewHelperManager must
        // not create any helpers before this happens. Otherwise the created
        // helper would have an invalid renderer. To guarantee correct setup
        // independent of order, do not define factories, but create the
        // instances directly here.
        $viewHelperManager = new HelperPluginManager($serviceManager);
        $viewHelperManager->configure($config['view_helpers']);
        $serviceManager->setService('ViewHelperManager', $viewHelperManager);

        $phpRenderer = new PhpRenderer();
        $phpRenderer->setHelperPluginManager($viewHelperManager);
        $phpRenderer->setResolver($serviceManager->get('ViewResolver'));
        $serviceManager->setService(PhpRenderer::class, $phpRenderer);

        // The Doctype helper is not registered by default.
        // @codeCoverageIgnoreStart
        $doctypeFactory = function (): Doctype {
            $helper = new Doctype();
            $helper->setDoctype(Doctype::HTML5);
            return $helper;
        };
        $viewHelperManager->setFactory(Doctype::class, $doctypeFactory);
        $viewHelperManager->setFactory('laminasviewhelperdoctype', $doctypeFactory);
        // @codeCoverageIgnoreEnd

        // Abstract factories are invoked in the same order in which they get
        // added. The abstract DI factory should act as a fallback only. It
        // cannot be added via config because other modules might add another
        // abstract factory after the DI factory.
        $serviceManager->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(FilterPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $serviceManager->get(ValidatorPluginManager::class)->addAbstractFactory(AutowireFactory::class);
        $viewHelperManager->addAbstractFactory(AutowireFactory::class);

        return $serviceManager;
    }
}
