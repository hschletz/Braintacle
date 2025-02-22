<?php

namespace Braintacle\Software;

use Formotron\DataProcessor;
use Model\SoftwareManager;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Accept/Ignore software.
 */
class SoftwareManagementHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private SoftwareManager $softwareManager,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $postData = $request->getParsedBody();

        $formData = $this->dataProcessor->process($postData, SoftwareFormData::class);
        $action = match ($formData->action) {
            Action::Accept => true,
            Action::Ignore => false,
        };

        foreach ($formData->software as $name) {
            $this->softwareManager->setDisplay($name, $action);
        }

        return $this->response
            ->withStatus(302)
            ->withHeader('Location', (string) $request->getUri());
    }
}
