<?php

namespace Braintacle\Http;

use Braintacle\Authentication;
use Braintacle\Client;
use Braintacle\Duplicates;
use Braintacle\Group;
use Braintacle\Legacy\ApplicationBridge;
use Braintacle\Software;
use Nyholm\Psr7\Response;
use Psr\Container\ContainerInterface;
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
     *
     * @param App<ContainerInterface> $app
     */
    public static function setup(App $app): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong

        // Login routes must not have LoginMiddleware attached.
        $app->get('/login', Authentication\ShowLoginFormHandler::class)->setName('loginPage');
        $app->post('/login', Authentication\ProcessLoginFormHandler::class)->setName('loginHandler');
        $app->get('/logout', Authentication\LogoutHandler::class)->setName('logout');

        // Prevent browser-initiated requests for favicon from being redirected
        // to the login page. This route is only encountered if the site admin
        // did not place a favicon in the /public directory or map a path in the
        // webserver config.
        $app->get('/favicon.ico', fn() => new Response(404));

        // All other routes get LoginMiddleware.
        $app->group('', function (RouteCollectorProxyInterface $group) {
            $group->get('/client{id}/bios', Client\SubPage\Bios::class)->setName('showClientBios');
            $group->get('/client/{id}/configuration', Client\Configuration\ShowConfigurationHandler::class)->setName('showClientConfiguration');
            $group->post('/client/{id}/configuration', Client\Configuration\SetConfigurationHandler::class)->setName('setClientConfiguration');
            $group->get('/client/{id}/export', Client\ExportHandler::class)->setName('export');
            $group->get('/client/{id}/general', Client\SubPage\General::class)->setName('showClientGeneral');
            $group->get('/client/{id}/groups', Client\Groups\GroupsPageHandler::class)->setName('showClientGroups');
            $group->post('/client/{id}/groups', Client\Groups\SetMembershipsHandler::class)->setName('manageGroupMemberships');
            $group->get('/client/{id}/packages', Client\Packages\ShowPackagesHandler::class)->setName('showClientPackages');
            $group->post('/client/{id}/packages', Client\Packages\AssignPackagesHandler::class)->setName('assignPackageToClient');
            $group->put('/client/{id}/packages', Client\Packages\ResetPackageHandler::class)->setName('resetPackageOnClient');
            $group->delete('/client/{id}/packages', Client\Packages\RemovePackageHandler::class)->setName('removePackageFromClient');
            $group->get('/client/{id}/software', Client\Software\SoftwarePageHandler::class)->setName('showClientSoftware');
            $group->get('/duplicates', Duplicates\OverviewHandler::class)->setName('duplicatesList');
            $group->post('/duplicates', Duplicates\MergeDuplicatesHandler::class)->setName('mergeDuplicates');
            $group->post('/duplicates/allow', Duplicates\AllowDuplicatesHandler::class)->setName('allowDuplicates');
            $group->get('/duplicates/{criterion}', Duplicates\ManageDuplicatesHandler::class)->setName('manageDuplicates');
            $group->post('/group', Group\Add\AddToGroupFormHandler::class)->setName('addGroup');
            $group->delete('/group', Group\DeleteHandler::class)->setName('deleteGroup');
            $group->get('/group/configuration', Group\Configuration\ShowConfigurationHandler::class)->setName('showGroupConfiguration');
            $group->post('/group/configuration', Group\Configuration\SetConfigurationHandler::class)->setName('setGroupConfiguration');
            $group->get('/group/excluded', Group\Members\ExcludedPageHandler::class)->setName('showGroupExcluded');
            $group->get('/group/general', Group\GeneralPageHandler::class)->setName('showGroupGeneral');
            $group->get('/group/members', Group\Members\MembersPageHandler::class)->setName('showGroupMembers');
            $group->get('/group/packages', Group\Packages\ShowPackagesHandler::class)->setName('showGroupPackages');
            $group->post('/group/packages', Group\Packages\AssignPackagesHandler::class)->setName('assignPackageToGroup');
            $group->delete('/group/packages', Group\Packages\RemovePackagesHandler::class)->setName('removePackageFromGroup');
            $group->get('/groups', Group\Overview\OverviewHandler::class)->setName('groupList');
            $group->get('/software', Software\SoftwarePageHandler::class)->setName('softwarePage');
            $group->post('/software', Software\SoftwareManagementHandler::class)->setName('softwareHandler');

            // Legacy routes, listed here to provide a name for MVC routes, or
            // to provide an alternative MVC-style URL that is composed by
            // legacy methods.
            $group->get('/console/accounts/index', ApplicationBridge::class)->setName('preferencesUsersList');
            $group->get('/console/client/customfields', ApplicationBridge::class)->setName('showClientCustomFields');
            $group->get('/console/client/delete', ApplicationBridge::class)->setName('deleteClient');
            $group->get('/console/client/display', ApplicationBridge::class)->setName('showClientDisplay');
            $group->get('/console/client/import', ApplicationBridge::class)->setName('importPage');
            $group->get('/console/client/index', ApplicationBridge::class)->setName('clientList');
            $group->get('/console/client/misc', ApplicationBridge::class)->setName('showClientMisc');
            $group->get('/console/client/msoffice', ApplicationBridge::class)->setName('showClientMsOffice');
            $group->get('/console/client/network', ApplicationBridge::class)->setName('showClientNetwork');
            $group->get('/console/client/printers', ApplicationBridge::class)->setName('showClientPrinters');
            $group->get('/console/client/registry', ApplicationBridge::class)->setName('showClientRegistry');
            $group->get('/console/client/search', ApplicationBridge::class)->setName('searchPage');
            $group->get('/console/client/storage', ApplicationBridge::class)->setName('showClientStorage');
            $group->get('/console/client/system', ApplicationBridge::class)->setName('showClientSystem');
            $group->get('/console/client/virtualmachines', ApplicationBridge::class)->setName('showClientVirtualMachines');
            $group->get('/console/client/windows', ApplicationBridge::class)->setName('showClientWindows');
            $group->get('/console/group/add/', Group\Add\AddToGroupPageHandler::class)->setName('addGroupPage');
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
