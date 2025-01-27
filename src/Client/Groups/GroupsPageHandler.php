<?php

namespace Braintacle\Client\Groups;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Group\GroupManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Display and manage group memberships.
 */
class GroupsPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private GroupManager $groupManager,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $effectiveMemberships = [];
        $formData = [];
        $groups = $this->groupManager->getGroups(null, null, 'Name');
        if (count($groups)) {
            $memberships = $client->getGroupMemberships(Client::MEMBERSHIP_ANY);
            foreach ($groups as $group) {
                $id = $group->id;
                $name = $group->name;
                if (isset($memberships[$id])) {
                    $type = $memberships[$id];
                    $formData[$name] = $type;
                    if ($type != Client::MEMBERSHIP_NEVER) {
                        $effectiveMemberships[$name] = $type;
                    }
                } else {
                    // Default to automatic membership
                    $formData[$name] = Client::MEMBERSHIP_AUTOMATIC;
                }
            }
        }

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Groups.latte', [
            'client' => $client,
            'currentAction' => 'groups',
            'memberships' => $effectiveMemberships,
            'formData' => $formData,
        ]));

        return $this->response;
    }
}
