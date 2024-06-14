<?php

use Braintacle\Container;
use Braintacle\Http\ErrorHandlingMiddleware;
use Braintacle\Http\LoginMiddleware;
use Braintacle\Http\RouteHelperMiddleware;
use Braintacle\Http\RouteHelper;
use Braintacle\Legacy\ApplicationBridge;
use Slim\Factory\AppFactory;
use Slim\Handlers\Strategies\RequestHandler;
use Slim\Interfaces\RouteCollectorProxyInterface;

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

$app = AppFactory::createFromContainer($container);
$app->getRouteCollector()->setDefaultInvocationStrategy(new RequestHandler());
$app->setBasePath(RouteHelper::getBasePath($_SERVER));

$app->add(RouteHelperMiddleware::class);
$app->addRoutingMiddleware();
$app->add(ErrorHandlingMiddleware::class);

// Login routes must not have LoginMiddleware attached.
$app->get('/console/login/login', ApplicationBridge::class)->setName('loginPage');
$app->post('/console/login/login', ApplicationBridge::class);

// All other routes get LoginMiddleware.
$app->group('', function (RouteCollectorProxyInterface $group) {
    // Catch-all route: forward to MVC application
    $group->any('{path:.*}', ApplicationBridge::class);
})->add(LoginMiddleware::class);

$app->run();
