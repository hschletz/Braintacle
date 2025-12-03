<?php

namespace Braintacle\Search;

use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private SearchFilters $searchFilters,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $context = [
            'filters' => $this->searchFilters->getFilters(),
            'types' => $this->searchFilters->getNonTextTypes(),
        ];
        if ($request->getQueryParams()) {
            $context['searchParams'] = $this->dataProcessor->process($request->getQueryParams(), SearchParams::class);
        };

        $this->response->getBody()->write($this->templateEngine->render('Pages/Search/SearchForm.latte', $context));

        return $this->response;
    }
}
