<?php

namespace Braintacle\Test\Group\Configuration;

use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Braintacle\Group\Configuration\SetConfigurationHandler;
use Braintacle\Group\Group;
use Braintacle\Group\GroupRequestParameters;
use Braintacle\Test\HttpHandlerTestTrait;
use Formotron\DataProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetConfigurationHandler::class)]
final class SetConfigurationHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    public function testHandler()
    {
        $queryParams = ['name' => 'test'];
        $formData = ['key' => 'value'];

        $group = new Group();

        $groupRequestParameters = new GroupRequestParameters();
        $groupRequestParameters->group = $group;

        $configurationParameters = new GroupConfigurationParameters();

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$queryParams, GroupRequestParameters::class, $groupRequestParameters],
            [$formData, GroupConfigurationParameters::class, $configurationParameters],
        ]);

        $clientConfig = $this->createMock(ClientConfig::class);
        $clientConfig->expects($this->once())->method('setOptions')->with($group, $configurationParameters);

        $handler = new SetConfigurationHandler($this->response, $dataProcessor, $clientConfig);

        $response = $handler->handle(
            $this->request
                ->withQueryParams($queryParams)
                ->withParsedBody($formData)
                ->withUri($this->uri)
        );
        $this->assertResponseStatusCode(302, $response);
        $this->assertResponseHeaders(['Location' => [$this->uri]], $response);
    }
}
