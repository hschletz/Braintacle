<?php

namespace Braintacle\Group\Overview;

use Braintacle\FlashMessages;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Group\GroupManager;
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
        private GroupManager $groupManager,
        private TemplateEngine $templateEngine,
        private FlashMessages $flashMessages,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestParameters = $this->dataProcessor->process($request->getQueryParams(), OverviewRequestParameters::class);
        $groups = $this->groupManager->getGroups(
            null,
            null,
            $requestParameters->order->name,
            $requestParameters->direction->value,
        );
        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/Overview.latte', [
            'groups' => $groups,
            'order' => $requestParameters->order->name,
            'direction' => $requestParameters->direction,
            'message' => $this->flashMessages->get(FlashMessages::Success)[0] ?? null,
        ]));

        return $this->response;
    }
}
