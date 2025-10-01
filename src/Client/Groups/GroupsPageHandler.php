<?php

namespace Braintacle\Client\Groups;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Clients;
use Braintacle\Group\Membership;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Group\GroupManager;
use Override;
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
        private Clients $clients,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $effectiveMemberships = [];
        $formData = [];
        $groups = $this->groupManager->getGroups(null, null, 'Name');
        if (count($groups)) {
            $memberships = $this->clients->getGroupMemberships(
                $client,
                Membership::Automatic,
                Membership::Manual,
                Membership::Never,
            );
            foreach ($groups as $group) {
                $id = $group->id;
                $name = $group->name;
                if (isset($memberships[$id])) {
                    $type = $memberships[$id];
                    $formData[$name] = $type->value;
                    if ($type != Membership::Never) {
                        $effectiveMemberships[$name] = $type->value;
                    }
                } else {
                    // Default to automatic membership
                    $formData[$name] = Membership::Automatic->value;
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
