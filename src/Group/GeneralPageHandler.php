<?php

namespace Braintacle\Group;

use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Show general information about a group.
 */
class GeneralPageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $group = $this->dataProcessor->process($request->getQueryParams(), GroupRequestParameters::class)->group;
        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/General.latte', [
            'group' => $group,
            'currentAction' => 'general',
        ]));

        return $this->response;
    }
}
