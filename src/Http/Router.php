<?php

namespace Braintacle\Http;

use Braintacle\Authentication;
use Braintacle\Client;
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
        $app->get('/logout', Authentication\LogoutHandler::class)->setName('logout');

        // All other routes get LoginMiddleware.
        $app->group('', function (RouteCollectorProxyInterface $group) {
            $group->get('/client/{id}/export', Client\ExportHandler::class)->setName('export');

            // Legacy routes handled by MVC application, which are listed here
            // to provide a route name.
            $group->get('/console/accounts/index', ApplicationBridge::class)->setName('preferencesUsersList');
            $group->get('/console/client/import', ApplicationBridge::class)->setName('importPage');
            $group->get('/console/client/index', ApplicationBridge::class)->setName('clientList');
            $group->get('/console/client/search', ApplicationBridge::class)->setName('searchPage');
            $group->get('/console/duplicates/index', ApplicationBridge::class)->setName('duplicatesList');
            $group->get('/console/group/index', ApplicationBridge::class)->setName('groupList');
            $group->get('/console/licenses/index', ApplicationBridge::class)->setName('licensesPage');
            $group->get('/console/network/index', ApplicationBridge::class)->setName('networkPage');
            $group->get('/console/package/build', ApplicationBridge::class)->setName('packageBuildPage');
            $group->get('/console/package/index', ApplicationBridge::class)->setName('packagesList');
            $group->get('/console/preferences/agent', ApplicationBridge::class)->setName('preferencesAgentPage');
            $group->get('/console/preferences/display', ApplicationBridge::class)->setName('preferencesDisplayPage');
            $group->get('/console/preferences/download', ApplicationBridge::class)->setName('preferencesDownloadPage');
            $group->get('/console/preferences/filters', ApplicationBridge::class)->setName('preferencesFiltersPage');
            $group->get('/console/preferences/groups', ApplicationBridge::class)->setName('preferencesGroupsPage');
            $group->get('/console/preferences/index', ApplicationBridge::class)->setName('preferencesPage');
            $group->get('/console/preferences/inventory', ApplicationBridge::class)->setName('preferencesInventoryPage');
            $group->get('/console/preferences/networkscanning', ApplicationBridge::class)->setName('preferencesNetworkScanningPage');
            $group->get('/console/preferences/packages', ApplicationBridge::class)->setName('preferencesPackagesPage');
            $group->get('/console/preferences/rawdata', ApplicationBridge::class)->setName('preferencesRawDataPage');
            $group->get('/console/preferences/system', ApplicationBridge::class)->setName('preferencesSystemPage');
            $group->get('/console/software/index', ApplicationBridge::class)->setName('softwarePage');

            // Catch-all route: forward to MVC application
            $group->any('{path:.*}', ApplicationBridge::class);
        })->add(LoginMiddleware::class);
    }
}
