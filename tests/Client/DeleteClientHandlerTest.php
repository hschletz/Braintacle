<?php

namespace Braintacle\Test\Client;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\DeleteClientHandler;
use Braintacle\FlashMessages;
use Braintacle\Http\RouteHelper;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use Laminas\Translator\TranslatorInterface;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeleteClientHandler::class)]
final class DeleteClientHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public static function handlerProvider()
    {
        return [
            [[], false],
            [['delete_interfaces' => ''], true],
        ];
    }

    #[DataProvider('handlerProvider')]
    public function testHandler(array $queryParams, bool $deleteInterfaces)
    {
        $clientId = 42;
        $routeArguments = ['id' => $clientId];

        $clientName = 'client_name';
        $client = new Client();
        $client->name = $clientName;

        $requestParameters = new ClientRequestParameters();
        $requestParameters->client = $client;

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($routeArguments, ClientRequestParameters::class)
            ->willReturn($requestParameters);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())->method('deleteClient')->with($client, $deleteInterfaces);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn($message) => '_' . $message);

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->expects($this->once())->method('add')->with(
            FlashMessages::Success,
            "_Client 'client_name' was successfully deleted.",
        );

        $handler = new DeleteClientHandler(
            $this->response,
            $routeHelper,
            $dataProcessor,
            $clientManager,
            $translator,
            $flashMessages,
        );
        $response = $handler->handle($this->request->withQueryParams($queryParams));

        $this->assertResponseStatusCode(200, $response);
    }
}
