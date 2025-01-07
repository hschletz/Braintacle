<?php

namespace Braintacle;

use Braintacle\Database\AdapterFactory;
use Braintacle\Database\DatabaseFactory;
use Braintacle\I18n\Translator;
use Braintacle\Legacy\ClientOrGroupFactory;
use Braintacle\Template\TemplateEngine;
use Composer\InstalledVersions;
use Console\Template\TemplateLoader;
use DI\Container as DIContainer;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Mvc\Application as MvcApplication;
use Laminas\Translator\TranslatorInterface;
use Laminas\Session\Validator\Csrf;
use Library\Application;
use Locale;
use Model\Client\Client;
use Model\Group\Group;
use Model\Operator\AuthenticationService;
use Model\Package\Storage\Direct as DirectStorage;
use Model\Package\Storage\StorageInterface;
use Monolog\Logger;
use Nada\Database\AbstractDatabase;
use Nyholm\Psr7\Response;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

use function DI\autowire;
use function DI\create;
use function DI\factory;
use function DI\get;

class Container extends DIContainer
{
    public function __construct()
    {
        $rootPath = InstalledVersions::getRootPackage()['install_path'];
        $locale = Locale::getDefault();

        parent::__construct([
            AbstractDatabase::class => factory(DatabaseFactory::class),
            Adapter::class => factory(AdapterFactory::class),
            AppConfig::class => create(AppConfig::class)->constructor(
                new Filesystem(),
                getenv('BRAINTACLE_CONFIG') ?: InstalledVersions::getRootPackage()['install_path'] . '/config/braintacle.ini',
            ),
            AuthenticationServiceInterface::class => get(AuthenticationService::class),
            Client::class => factory(ClientOrGroupFactory::class),
            ClockInterface::class => get(Clock::class),
            Csrf::class => create(Csrf::class)->constructor(['timeout' => null]),
            Group::class => factory(ClientOrGroupFactory::class),
            LoggerInterface::class => create(Logger::class)->constructor('Braintacle'),
            MvcApplication::class => factory(Application::init(...))->parameter('module', 'Console'),
            ResponseInterface::class => get(Response::class),
            StorageInterface::class => get(DirectStorage::class),
            TemplateEngine::class => autowire()->constructor(locale: $locale),
            TemplateLoader::class => create(TemplateLoader::class)->constructor($rootPath . 'templates'),
            TranslatorInterface::class => create(Translator::class)->constructor(
                $locale,
                $rootPath . '/i18n',
                get(AppConfig::class)
            ),
        ]);
    }
}
