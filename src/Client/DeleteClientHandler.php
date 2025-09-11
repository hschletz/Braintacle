<?php

namespace Braintacle\Client;

use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Client\ClientManager;
use Override;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Delete client.
 */
final class DeleteClientHandler implements RequestHandlerInterface
{
    public function __construct(
        private ResponseInterface $response,
        private RouteHelper $routeHelper,
        private DataProcessor $dataProcessor,
        private ClientManager $clientManager,
        private TranslatorInterface $translator,
        private FlashMessages $flashMessages,
    ) {}

    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $client = $this->dataProcessor->process(
            $this->routeHelper->getRouteArguments(),
            ClientRequestParameters::class,
        )->client;

        $this->clientManager->deleteClient(
            $client,
            isset($request->getQueryParams()['delete_interfaces']),
        );
        $this->flashMessages->add(
            FlashMessages::Success,
            sprintf(
                $this->translator->translate("Client '%s' was successfully deleted."),
                $client->name,
            ),
        );

        return $this->response;
    }
}
