<?php

namespace Braintacle\Client\Import;

use Braintacle\FlashMessages;
use Braintacle\Template\TemplateEngine;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Client import (file upload) form.
 */
final class ImportPage implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private FlashMessages $flashMessages,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->response->getBody()->write($this->templateEngine->render('Pages/Import.latte', [
            'subMenuRoute' => 'importPage',
            'error' => $this->flashMessages->get(FlashMessages::Error)[0] ?? null,
        ]));

        return $this->response;
    }
}
