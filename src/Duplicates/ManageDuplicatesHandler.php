<?php

namespace Braintacle\Duplicates;

use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Client\DuplicatesManager;
use Model\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Manage duplicates with given criterion.
 */
class ManageDuplicatesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private DuplicatesManager $duplicatesManager,
        private Config $config,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestParameters = $this->dataProcessor->process(
            $this->routeHelper->getRouteArguments() + $request->getQueryParams(),
            DuplicatesRequestParameters::class
        );
        $clients = $this->duplicatesManager->find(
            $requestParameters->criterion,
            $requestParameters->order,
            $requestParameters->direction,
        );

        $this->response->getBody()->write($this->templateEngine->render('Pages/Duplicates/ManageDuplicates.latte', [
            'clients' => $clients,
            'criterion' => $requestParameters->criterion,
            'config' => $this->config,
            'order' => $requestParameters->order->value,
            'direction' => $requestParameters->direction,
        ]));

        return $this->response;
    }
}
