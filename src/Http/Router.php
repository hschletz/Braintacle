<?php

namespace Braintacle\Http;

use Braintacle\Authentication;
use Braintacle\Legacy\ApplicationBridge;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * HTTP router.
 *
 * @codeCoverageIgnore
 */
class Router
{
    /**
     * Set up routes.
     */
    public static function setup(App $app): void
    {
        // Login routes must not have LoginMiddleware attached.
        $app->get('/login', Authentication\ShowLoginFormHandler::class)->setName('loginPage');
        $app->post('/login', Authentication\ProcessLoginFormHandler::class)->setName('loginHandler');
        $app->get('/logout', Authentication\LogoutHandler::class)->setName(Authentication\LogoutHandler::class);

        // All other routes get LoginMiddleware.
        $app->group('', function (RouteCollectorProxyInterface $group) {
            // Legacy routes handled by MVC application, which are listed here
            // to provide a route name.
            $group->get('/console/client/index', ApplicationBridge::class)->setName('clientList');

            // Catch-all route: forward to MVC application
            $group->any('{path:.*}', ApplicationBridge::class);
        })->add(LoginMiddleware::class);
    }
}
