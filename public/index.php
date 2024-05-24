<?php

use Braintacle\AppConfig;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Legacy\ApplicationBridge;
use DI\Container;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\Log\Formatter\Simple as SimpleFormatter;
use Laminas\Log\Logger;
use Laminas\Log\Processor\PsrPlaceholder;
use Laminas\Log\PsrLoggerAdapter;
use Laminas\Log\Writer\Stream as StreamWriter;
use Laminas\Mvc\Application as MvcApplication;
use Library\Application;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestHandler;

use function DI\create;
use function DI\factory;
use function DI\get;

error_reporting(-1);

require_once('../vendor/autoload.php');

$writer = new StreamWriter(STDERR);
$writer->setFormatter(new SimpleFormatter('%timestamp% Braintacle %priorityName%: %message% %extra%'));
$logger = new Logger();
$logger->addProcessor(new PsrPlaceholder());
$logger->addWriter($writer);

$container = new Container([
    AppConfig::class => create(AppConfig::class)->constructor(
        new IniReader(),
        getenv('BRAINTACLE_CONFIG') ?: null
    ),
    LoggerInterface::class => create(PsrLoggerAdapter::class)->constructor($logger),
    MvcApplication::class => factory(Application::init(...))->parameter('module', 'Console'),
    ResponseInterface::class => get(Response::class),
]);

$app = AppFactory::createFromContainer($container);
$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestHandler());

$app->addRoutingMiddleware();
$app->add(ErrorHandlingMiddleware::class);

$app->any('{path:.*}', ApplicationBridge::class); // Catch-all route: forward to MVC application

$app->run();
