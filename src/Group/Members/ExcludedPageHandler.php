<?php

namespace Braintacle\Group\Members;

use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Client\ClientManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show excluded clients.
 */
class ExcludedPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private ClientManager $clientManager,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestParams = $this->dataProcessor->process($request->getQueryParams(), ExcludedRequestParameters::class);
        $clients = $this->clientManager->getClients(
            ['Name', 'UserName', 'InventoryDate'],
            $requestParams->order->name,
            $requestParams->direction->value,
            'ExcludedFrom',
            $requestParams->group,
        );

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/Excluded.latte', [
            'group' => $requestParams->group,
            'currentAction' => 'excluded',
            'clients' => $clients,
            'order' => $requestParams->order->name,
            'direction' => $requestParams->direction,
        ]));

        return $this->response;
    }
}
