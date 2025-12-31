<?php

namespace Braintacle\Client\Import;

use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Laminas\Session\Validator\Csrf as CsrfValidator;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;

/**
 * Import client from uploaded file.
 */
class ImportHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private CsrfValidator $csrfValidator,
        private Importer $importer,
        private RouteHelper $routeHelper,
        private FlashMessages $flashMessages,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->csrfValidator->isValid($request->getParsedBody()['csrfToken'] ?? '')) {
            $messages = $this->csrfValidator->getMessages();
            throw new HttpBadRequestException($request, array_shift($messages));
        }

        $file = $request->getUploadedFiles()['file'] ?? null;
        if (! $file instanceof UploadedFileInterface) {
            throw new HttpBadRequestException($request, 'Missing or bad file');
        }
        $body = $file->getStream();

        try {
            $this->importer->importStream($body);

            return $this->response
                ->withStatus(302)
                ->withHeader('Location', $this->routeHelper->getPathForRoute('clientList'));
        } catch (ImportError $importError) {
            $this->flashMessages->add(FlashMessages::Error, $importError->getMessage());

            return $this->response
                ->withStatus(302)
                ->withHeader('Location', $this->routeHelper->getPathForRoute('importPage'));
        }
    }
}
