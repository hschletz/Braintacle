<?php

namespace Braintacle\Group\Members;

use Braintacle\Group\Groups;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show group members.
 */
class MembersPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Groups $groups,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestParams = $this->dataProcessor->process($request->getQueryParams(), MembersRequestParameters::class);
        $clients = $this->groups->getMembers($requestParams->group, $requestParams->order, $requestParams->direction);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/Members.latte', [
            'group' => $requestParams->group,
            'currentAction' => 'members',
            'clients' => $clients,
            'order' => $requestParams->order->name,
            'direction' => $requestParams->direction,
        ]));

        return $this->response;
    }
}
