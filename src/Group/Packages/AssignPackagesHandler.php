<?php

namespace Braintacle\Group\Packages;

use Braintacle\Group\GroupRequestParameters;
use Braintacle\Http\RouteHelper;
use Console\Form\Package\AssignPackagesForm;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Assign a packages to a group.
 */
class AssignPackagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private AssignPackagesForm $assignPackagesForm,
        private RouteHelper $routeHelper,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $formData = $request->getParsedBody();

        $group = $this->dataProcessor->process($queryParams, GroupRequestParameters::class)->group;
        $this->assignPackagesForm->process($formData, $group);

        return $this->response
            ->withStatus(302)
            ->withHeader(
                'Location',
                $this->routeHelper->getPathForRoute('showGroupPackages', queryParams: ['name' => $group->name])
            );
    }
}
