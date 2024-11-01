<?php

namespace Braintacle\Http;

use Braintacle\Authentication;
use Braintacle\Client;
use Braintacle\Group;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Software;
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
            $group->get('/group/packages', Group\Packages\ShowPackagesHandler::class)->setName('showGroupPackages');
            $group->post('/group/packages', Group\Packages\AssignPackagesHandler::class)->setName('assignPackageToGroup');
            $group->delete('/group/packages', Group\Packages\RemovePackagesHandler::class)->setName('removePackageFromGroup');
            $group->get('/software', Software\SoftwarePageHandler::class)->setName('softwarePage');
            $group->post('/software', Software\SoftwareManagementHandler::class)->setName('softwareHandler');

            // Legacy routes handled by MVC application, which are listed here
            // to provide a route name.
            $group->get('/console/accounts/index', ApplicationBridge::class)->setName('preferencesUsersList');
            $group->get('/console/client/import', ApplicationBridge::class)->setName('importPage');
            $group->get('/console/client/index', ApplicationBridge::class)->setName('clientList');
            $group->get('/console/client/search', ApplicationBridge::class)->setName('searchPage');
            $group->get('/console/duplicates/index', ApplicationBridge::class)->setName('duplicatesList');
            $group->get('/console/group/index', ApplicationBridge::class)->setName('groupList');
            $group->get('/console/group/general', ApplicationBridge::class)->setName('showGroupGeneral');
            $group->get('/console/group/members', ApplicationBridge::class)->setName('showGroupMembers');
            $group->get('/console/group/excluded', ApplicationBridge::class)->setName('showGroupExcluded');
            $group->get('/console/group/configuration', ApplicationBridge::class)->setName('showGroupConfiguration');
            $group->get('/console/group/delete', ApplicationBridge::class)->setName('showGroupDelete');
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

            // Catch-all route: forward to MVC application
            $group->any('{path:.*}', ApplicationBridge::class);
        })->add(LoginMiddleware::class);
    }
}
