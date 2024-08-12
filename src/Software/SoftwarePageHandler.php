<?php

namespace Braintacle\Software;

use Braintacle\Http\OrderHelper;
use Braintacle\Template\TemplateEngine;
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
        private OrderHelper $orderHelper,
        private TemplateEngine $templateEngine,
        private SoftwareManager $softwareManager,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filter = $query['filter'] ?? 'accepted';
        [$order, $direction] = ($this->orderHelper)($query, 'name');

        $software = $this->softwareManager->getSoftware(
            [
                'Os' => 'windows',
                'Status' => $filter,
            ],
            $order,
            $direction,
        );

        $this->response->getBody()->write(
            $this->templateEngine->render(
                'Pages/Software.latte',
                [
                    'software' => $software,
                    'order' => $order,
                    'direction' => $direction,
                    'filter' => $filter,
                ]
            )
        );

        return $this->response;
    }
}
