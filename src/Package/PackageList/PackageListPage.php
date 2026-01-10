<?php

namespace Braintacle\Package\PackageList;

use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Model\Package\PackageManager;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Package overview page.
 */
final class PackageListPage implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private DataProcessor $dataProcessor,
        private PackageManager $packageManager,
        private FlashMessenger $flashMessenger,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->dataProcessor->process($request->getQueryParams(), PackageListRequestParameters::class);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Package/PackageList.latte', [
            'packages' => $this->packageManager->getPackages(
                $query->order->name,
                $query->direction->value,
            ),
            'order' => $query->order->name,
            'direction' => $query->direction,
            'errorMessages' => $this->flashMessenger->getMessagesFromNamespace('error'),
            'successMessages' => $this->flashMessenger->getMessagesFromNamespace('success'),
        ]));

        return $this->response;
    }
}
