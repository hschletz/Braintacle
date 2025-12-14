<?php

namespace Braintacle;

use Braintacle\Database\ConnectionFactory;
use Braintacle\I18n\Translator;
use Braintacle\Legacy\ClientFactory;
use Braintacle\Legacy\Database\AdapterFactory;
use Braintacle\Legacy\Database\DatabaseFactory;
use Braintacle\Legacy\MvcApplication;
use Braintacle\Legacy\MvcApplicationFactory;
use Braintacle\Legacy\ServiceManagerFactory;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Composer\InstalledVersions;
use DI\Container as DIContainer;
use Doctrine\DBAL\Connection;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Laminas\Session\Validator\Csrf;
use Locale;
use Model\Client\Client;
use Model\Operator\AuthenticationService;
use Model\Package\Storage\Direct as DirectStorage;
use Model\Package\Storage\StorageInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Nada\Database\AbstractDatabase;
use Nyholm\Psr7\Response;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
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
            AdapterInterface::class => get(Adapter::class),
            AppConfig::class => create(AppConfig::class)->constructor(
                new Filesystem(),
                getenv('BRAINTACLE_CONFIG') ?:
                    InstalledVersions::getRootPackage()['install_path'] . '/config/braintacle.ini',
            ),
            AuthenticationServiceInterface::class => get(AuthenticationService::class),
            Client::class => factory(ClientFactory::class),
            ClockInterface::class => get(Time::class),
            Connection::class => factory(ConnectionFactory::class),
            Csrf::class => create(Csrf::class)->constructor(['timeout' => null]),
            LoggerInterface::class => create(Logger::class)->constructor(
                'Braintacle',
                [new StreamHandler('php://stderr', LogLevel::WARNING)],
            ),
            MvcApplication::class => factory(MvcApplicationFactory::class),
            ResponseInterface::class => get(Response::class),
            ServiceManager::class => factory(ServiceManagerFactory::class),
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
