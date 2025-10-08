<?php

namespace Braintacle\Group\Add;

use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Search\SearchParams;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Group\GroupManager;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show form for adding clients to new or existing group.
 */
class AddToGroupPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private GroupManager $groupManager,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $searchParams = $this->dataProcessor->process($request->getQueryParams(), SearchParams::class);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/AddToGroup.latte', [
            'groups' => $this->groupManager->getGroups(null, null, OverviewColumn::Name),
            'filter' => $searchParams->filter,
            'search' => $searchParams->search,
            'operator' => $searchParams->operator->value,
            'invert' => $searchParams->invert ? '1' : '0',
        ]));

        return $this->response;
    }
}
