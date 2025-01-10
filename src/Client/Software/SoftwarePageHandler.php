<?php

namespace Braintacle\Client\Software;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Config;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SoftwarePageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private Config $config,
        private TemplateEngine $templateEngine,
    ) {
    }

    /**
     * Information about a client's software
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;
        $queryParams = $this->dataProcessor->process($request->getQueryParams(), SoftwareQueryParams::class);

        /** @var iterable<SoftwarePageHandler> */
        $items = $client->getItems(
            'Software',
            $queryParams->order->value,
            $queryParams->direction->value,
            $this->config->displayBlacklistedSoftware ? [] : ['Software.NotIgnored' => null],
        );

        // Compact list by suppressing duplicate entries, adding the number of instances for each entry.
        $list = [];
        foreach ($items as $item) {
            $key = json_encode($item);
            $list[$key][] = $item;
        }

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Software.latte', [
            'client' => $client,
            'currentAction' => 'software',
            'order' => $queryParams->order->value,
            'direction' => $queryParams->direction,
            'list' => $list,
        ]));

        return $this->response;
    }
}
