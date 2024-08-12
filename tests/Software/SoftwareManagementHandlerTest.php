<?php

namespace Braintacle\Test\Software;

use Braintacle\Software\SoftwareManagementHandler;
use Braintacle\Test\HttpHandlerTestTrait;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\SoftwareManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SoftwareManagementHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;
    use MockeryPHPUnitIntegration;

    public static function actionProvider()
    {
        return [
            ['accept', true],
            ['ignore', false],
        ];
    }

    #[DataProvider('actionProvider')]
    public function testAction(string $action, bool $display)
    {
        $softwareManager = Mockery::mock(SoftwareManager::class);
        $softwareManager->shouldReceive('setDisplay')->with('software1', $display);
        $softwareManager->shouldReceive('setDisplay')->with('software2', $display);

        $request = $this->request
            ->withUri($this->uri)
            ->withParsedBody([
                'action' => $action,
                'software' => ['software1', 'software2'],
            ]);

        $handler = new SoftwareManagementHandler($this->response, $this->createFormProcessor(), $softwareManager);
        $response = $handler->handle($request);
        $this->assertResponseStatusCode(302, $response);
        $this->assertEquals([(string) $this->uri], $response->getHeader('Location'));
    }
}
