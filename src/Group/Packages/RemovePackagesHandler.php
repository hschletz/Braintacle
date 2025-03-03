<?php

namespace Braintacle\Group\Packages;

use Braintacle\Package\Assignments;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Remove packages from a group.
 */
class RemovePackagesHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private Assignments $assignments,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $params = $this->dataProcessor->process($queryParams, RemovePackagesParameters::class);

        $this->assignments->unassignPackage($params->packageName, $params->group);

        return $this->response;
    }
}
