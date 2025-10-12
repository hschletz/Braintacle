<?php

namespace Braintacle\Group\Overview;

use Braintacle\FlashMessages;
use Braintacle\Group\Groups;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show table with overview of groups.
 */
class OverviewHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Groups $groups,
        private TemplateEngine $templateEngine,
        private FlashMessages $flashMessages,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestParameters = $this->dataProcessor->process(
            $request->getQueryParams(),
            OverviewRequestParameters::class
        );
        $groups = $this->groups->getGroups($requestParameters->order, $requestParameters->direction);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/Overview.latte', [
            'groups' => $groups,
            'order' => $requestParameters->order->name,
            'direction' => $requestParameters->direction,
            'message' => $this->flashMessages->get(FlashMessages::Success)[0] ?? null,
        ]));

        return $this->response;
    }
}
