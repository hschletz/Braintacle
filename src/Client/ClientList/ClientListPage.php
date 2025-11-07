<?php

namespace Braintacle\Client\ClientList;

use Braintacle\Client\Clients;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show all clients.
 */
final class ClientListPage implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Clients $clients,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->dataProcessor->process($request->getQueryParams(), ClientListRequestData::class);

        $this->response->getBody()->write($this->templateEngine->render('Pages/ClientList.latte', [
            'order' => $query->order->name,
            'direction' => $query->direction,
            'clients' => $this->clients->getClientList($query->order, $query->direction),
        ]));

        return $this->response;
    }
}
