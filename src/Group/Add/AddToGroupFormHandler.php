<?php

namespace Braintacle\Group\Add;

use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Model\Group\GroupManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Process form for adding clients to new or existing group.
 */
class AddToGroupFormHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private GroupManager $groupManager,
        private RouteHelper $routeHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['description'])) {
            $formData = $this->dataProcessor->process($request->getParsedBody(), NewGroupFormData::class);
            $this->groupManager->createGroup($formData->name, $formData->description);
            $group = $this->groupManager->getGroup($formData->name);
        } else {
            $formData = $this->dataProcessor->process($request->getParsedBody(), ExistingGroupFormData::class);
            $group = $formData->group;
        }
        $group->setMembersFromQuery(
            $formData->membershipType->value,
            $formData->filter,
            $formData->search,
            $formData->operator->value,
            $formData->invert,
        );

        return $this->response->withStatus(302)->withHeader(
            'Location',
            $this->routeHelper->getPathForRoute('showGroupMembers', [], ['name' => $group->name])
        );
    }
}
