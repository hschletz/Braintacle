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
 * Show excluded clients.
 */
class ExcludedPageHandler implements RequestHandlerInterface
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
        $requestParams = $this->dataProcessor->process($request->getQueryParams(), ExcludedRequestParameters::class);

        $clients = $this->groups->getExcludedClients(
            $requestParams->group,
            $requestParams->order,
            $requestParams->direction
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
