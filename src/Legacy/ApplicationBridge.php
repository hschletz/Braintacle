<?php

namespace Braintacle\Legacy;

use Braintacle\Template\TemplateEngine;
use Laminas\Http\Header\HeaderInterface;
use Laminas\Http\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Invoke the MVC application.
 */
class ApplicationBridge implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private MvcApplication $mvcApplication,
        private TemplateEngine $templateEngine,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $mvcEvent = $this->mvcApplication->run();
        /** @var Response */
        $mvcResponse = $mvcEvent->getResponse();

        // Generate PSR-7 response from MVC response.
        $response = $this->response->withStatus($mvcResponse->getStatusCode());
        /** @var HeaderInterface $header */
        foreach ($mvcResponse->getHeaders() as $header) {
            $response = $response->withAddedHeader($header->getFieldName(), $header->getFieldValue());
        }

        $template = $mvcEvent->getParam('template');
        if ($template) {
            $response->getBody()->write(
                $this->applyLayout(
                    $template,
                    $mvcResponse->getContent(),
                    $mvcEvent->getParam('subMenuRoute'),
                )
            );
        } else {
            $response->getBody()->write($mvcResponse->getContent());
        }

        return $response;
    }

    private function applyLayout(string $template, string $content, ?string $subMenuRoute): string
    {
        return $this->templateEngine->render(
            $template,
            [
                'content' => $content,
                'subMenuRoute' => $subMenuRoute,
            ]
        );
    }
}
