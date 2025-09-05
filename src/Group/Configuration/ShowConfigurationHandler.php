<?php

namespace Braintacle\Group\Configuration;

use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Group configuration form.
 */
final class ShowConfigurationHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private ClientConfig $clientConfig,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $group = $this->dataProcessor->process($request->getQueryParams(), GroupRequestParameters::class)->group;

        $values = $this->clientConfig->getOptions($group);
        $defaults = $this->clientConfig->getGlobalDefaults();

        $this->response->getBody()->write($this->templateEngine->render('Pages/Group/Configuration.latte', [
            'group' => $group,
            'currentAction' => 'configuration',
            'values' => $values,
            'defaults' => $defaults,
        ]));

        return $this->response;
    }
}
