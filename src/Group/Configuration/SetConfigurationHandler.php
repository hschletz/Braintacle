<?php

namespace Braintacle\Group\Configuration;

use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\GroupRequestParameters;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Set group configuration.
 */
final class SetConfigurationHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private ClientConfig $clientConfig,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $group = $this->dataProcessor->process($request->getQueryParams(), GroupRequestParameters::class)->group;
        $formData = $request->getParsedBody();
        $config = $this->dataProcessor->process($formData, GroupConfigurationParameters::class);
        $this->clientConfig->setOptions($group, $config);

        return $this->response->withStatus(302)->withHeader('Location', (string) $request->getUri());
    }
}
