<?php

namespace Braintacle;

use Braintacle\Database\AdapterFactory;
use Braintacle\Database\DatabaseFactory;
use Braintacle\I18n\Translator;
use Braintacle\Legacy\ClientOrGroupFactory;
use Braintacle\Logger\LoggerFactory;
use Composer\InstalledVersions;
use DI\Container as DIContainer;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\Db\Adapter\Adapter;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Mvc\Application as MvcApplication;
use Library\Application;
use Locale;
use Model\Client\Client;
use Model\Group\Group;
use Model\Operator\AuthenticationService;
use Model\Package\Storage\Direct as DirectStorage;
use Model\Package\Storage\StorageInterface;
use Nada\Database\AbstractDatabase;
use Nyholm\Psr7\Response;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

use function DI\create;
use function DI\factory;
use function DI\get;

class Container extends DIContainer
{
    public function __construct()
    {
        $rootPath = InstalledVersions::getRootPackage()['install_path'];

        parent::__construct([
            AbstractDatabase::class => factory(DatabaseFactory::class),
            Adapter::class => factory(AdapterFactory::class),
            AppConfig::class => create(AppConfig::class)->constructor(
                new IniReader(),
                getenv('BRAINTACLE_CONFIG') ?: InstalledVersions::getRootPackage()['install_path'] . '/config/braintacle.ini',
            ),
            AuthenticationServiceInterface::class => get(AuthenticationService::class),
            Client::class => factory(ClientOrGroupFactory::class),
            ClockInterface::class => get(Clock::class),
            Group::class => factory(ClientOrGroupFactory::class),
            LoggerInterface::class => factory(LoggerFactory::class),
            MvcApplication::class => factory(Application::init(...))->parameter('module', 'Console'),
            ResponseInterface::class => get(Response::class),
            StorageInterface::class => get(DirectStorage::class),
            TranslatorInterface::class => create(Translator::class)->constructor(
                Locale::getDefault(),
                $rootPath . '/i18n',
                get(AppConfig::class)
            ),
        ]);
    }
}
