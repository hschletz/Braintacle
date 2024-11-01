<?php

namespace Braintacle\Group\Packages;

use Formotron\FormProcessor;
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
        private FormProcessor $formProcessor,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $params = $this->formProcessor->process($queryParams, RemovePackagesParameters::class);

        $params->group->removePackage($params->packageName);

        return $this->response;
    }
}
