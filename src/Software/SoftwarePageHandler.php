<?php

namespace Braintacle\Software;

use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\SoftwareManager;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Display filter and software forms according to selected filter (default:
 * accepted)
 */
class SoftwarePageHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private TemplateEngine $templateEngine,
        private SoftwareManager $softwareManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $this->dataProcessor->process($request->getQueryParams(), SoftwarePageFormData::class);

        $software = $this->softwareManager->getSoftware(
            $queryParams->filter,
            $queryParams->order,
            $queryParams->direction,
        );

        $this->response->getBody()->write(
            $this->templateEngine->render(
                'Pages/Software.latte',
                [
                    'software' => $software,
                    'order' => $queryParams->order->value,
                    'direction' => $queryParams->direction,
                    'filter' => $queryParams->filter->value,
                ]
            )
        );

        return $this->response;
    }
}
