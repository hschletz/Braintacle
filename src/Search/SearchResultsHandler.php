<?php

namespace Braintacle\Search;

use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Client\ClientManager;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchResultsHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private ClientManager $clientManager,
        private SearchFilters $searchFilters,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $searchResults = $this->dataProcessor->process($request->getQueryParams(), SearchResults::class);

        $context = [
            'clients' => $this->clientManager->getClients(
                SearchResults::DefaultColumns, // getClients() will add column for search value if necessary
                $searchResults->order,
                $searchResults->direction->value,
                $searchResults->filter,
                $searchResults->search,
                $searchResults->operator->value,
                $searchResults->invert,
            ),
            'order' => $searchResults->order,
            'direction' => $searchResults->direction,
            'queryString' => $searchResults->toQueryString(),
        ];
        if (
            ($searchResults->invert || $searchResults->operator != SearchOperator::Equal) &&
            !in_array($searchResults->filter, SearchResults::DefaultColumns)
        ) {
            $context['extraColumn'] = $searchResults->filter;
            $context['extraHeader'] = $this->searchFilters->getFilters()[$searchResults->filter];
            $context['extraType'] = $this->searchFilters->getNonTextTypes()[$searchResults->filter] ?? 'text';
        }

        $this->response->getBody()->write($this->templateEngine->render('Pages/Search/SearchResults.latte', $context));

        return $this->response;
    }
}
