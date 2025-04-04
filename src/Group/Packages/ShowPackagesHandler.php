<?php

namespace Braintacle\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Package\Assignments;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
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
        private DataProcessor $dataProcessor,
        private Assignments $assignments,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $this->dataProcessor->process($request->getQueryParams(), GroupRequestParameters::class);
        $group = $params->group;

        $this->response->getBody()->write(
            $this->templateEngine->render(
                'Pages/Group/Packages.latte',
                [
                    'currentAction' => 'packages',
                    'group' => $group,
                    'assignedPackages' => $this->assignments->getAssignedPackages($group),
                    'assignablePackages' => $this->assignments->getAssignablePackages($group),
                ]
            )
        );

        return $this->response;
    }
}
