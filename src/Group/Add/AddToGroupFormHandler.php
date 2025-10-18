<?php

namespace Braintacle\Group\Add;

use Braintacle\Group\Groups;
use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Override;
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
        private Groups $groups,
        private RouteHelper $routeHelper,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (isset($parsedBody['description'])) {
            $formData = $this->dataProcessor->process($request->getParsedBody(), NewGroupFormData::class);
            $this->groups->createGroup($formData->name, $formData->description);
            $group = $this->groups->getGroup($formData->name);
        } else {
            $formData = $this->dataProcessor->process($request->getParsedBody(), ExistingGroupFormData::class);
            $group = $formData->group;
        }

        $this->groups->setSearchResults($group, $formData, $formData->membershipType);

        return $this->response->withStatus(302)->withHeader(
            'Location',
            $this->routeHelper->getPathForRoute('showGroupMembers', [], ['name' => $group->name])
        );
    }
}
