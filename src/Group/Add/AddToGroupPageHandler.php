<?php

namespace Braintacle\Group\Add;

use Braintacle\Group\Groups;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Search\SearchParams;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
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
        private Groups $groups,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $searchParams = $this->dataProcessor->process($request->getQueryParams(), SearchParams::class);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/AddToGroup.latte', [
            'groups' => $this->groups->getGroups(OverviewColumn::Name),
            'filter' => $searchParams->filter,
            'search' => $searchParams->search,
            'operator' => $searchParams->operator->value,
            'invert' => $searchParams->invert ? '1' : '0',
        ]));

        return $this->response;
    }
}
