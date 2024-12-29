<?php

namespace Braintacle\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Template\TemplateEngine;
use Formotron\FormProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Manage packages for a group.
 */
class ShowPackagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private FormProcessor $formProcessor,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $this->formProcessor->process($request->getQueryParams(), GroupRequestParameters::class);
        $group = $params->group;

        $this->response->getBody()->write(
            $this->templateEngine->render(
                'Pages/Group/Packages.latte',
                [
                    'currentAction' => 'packages',
                    'group' => $group,
                    'assignedPackages' => $group->getPackages('asc'),
                    'assignablePackages' => $group->getAssignablePackages(),
                ]
            )
        );

        return $this->response;
    }
}