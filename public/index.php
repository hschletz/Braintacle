<?php

namespace Braintacle;

use Braintacle\Container;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Http\RouteHelperMiddleware;
use Braintacle\Http\RouteHelper;
use Braintacle\Http\Router;
use IntlException;
use Locale;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestHandler;

error_reporting(-1);

require_once __DIR__ . '/../vendor/autoload.php';

// Detect and set locale early because it will be evaluated in container setup.
// Ignore any errors caused by invalid Accept-Language header, independent of
// intl.use_exceptions setting.
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    try {
        $locale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        if ($locale) {
            Locale::setDefault($locale);
        }
    } catch (IntlException) {
    }
}

$container = new Container();

/** @var App<ContainerInterface> */
$app = AppFactory::createFromContainer($container);
$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestHandler(true));
$app->setBasePath(RouteHelper::detectBasePath($_SERVER));

$app->add(RouteHelperMiddleware::class);
$app->addRoutingMiddleware();
$app->add(ErrorHandlingMiddleware::class);

Router::setup($app);

$app->run();
