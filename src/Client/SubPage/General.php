<?php

namespace Braintacle\Client\SubPage;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * General information about a client.
 */
class General implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;

        $physicalRam = 0;
        foreach ($client['MemorySlot'] as $slot) {
            $physicalRam += $slot->size;
        }

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/General.latte', [
            'client' => $client,
            'currentAction' => 'general',
            'physicalRam' => $physicalRam,
        ]));

        return $this->response;
    }
}
