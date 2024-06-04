<?php

use Braintacle\Container;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Legacy\ApplicationBridge;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestHandler;

error_reporting(-1);

require_once('../vendor/autoload.php');

$container = new Container();

$app = AppFactory::createFromContainer($container);
$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestHandler());

$app->addRoutingMiddleware();
$app->add(ErrorHandlingMiddleware::class);

$app->any('{path:.*}', ApplicationBridge::class); // Catch-all route: forward to MVC application

$app->run();
