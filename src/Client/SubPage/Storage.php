<?php

namespace Braintacle\Client\SubPage;

use Braintacle\Client\ClientDetails;
use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\OsType;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Formotron\DataProcessor;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Information about a client's storage devices and filesystems.
 */
class Storage implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private ClientDetails $clientDetails,
        private TemplateEngine $templateEngine,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeArguments = $this->routeHelper->getRouteArguments();
        $client = $this->dataProcessor->process($routeArguments, ClientRequestParameters::class)->client;
        $os = $this->clientDetails->getOsType($client);

        $this->response->getBody()->write($this->templateEngine->render('Pages/Client/Storage.latte', [
            'client' => $client,
            'devices' => $client->getItems('storageDevice'),
            'filesystems' => $client->getItems('filesystem'),
            'deviceShow' => [
                'manufacturer' => $os == OsType::Unix,
                'productName' => $os != OsType::Android,
                'type' => $os != OsType::Unix,
                'device' => $os == OsType::Unix,
                'serial' => $os != OsType::Android,
                'firmware' => $os != OsType::Android,
            ],
            'fsShow' => [
                'letter' => $os == OsType::Windows,
                'label' => $os == OsType::Windows,
                'type' => $os == OsType::Windows,
                'mountpoint' => $os != OsType::Windows,
                'device' => $os != OsType::Windows,
                'creationDate' => $os == OsType::Unix,
            ],
            'currentAction' => 'storage',
        ]));

        return $this->response;
    }
}
