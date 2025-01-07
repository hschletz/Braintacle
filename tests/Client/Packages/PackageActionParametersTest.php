<?php

namespace Braintacle\Test\Client\Packages;

use Braintacle\Client\ClientTransformer;
use Braintacle\Client\Packages\PackageActionParameters;
use Braintacle\Test\DataProcessorTestTrait;
use Model\Client\Client;
use PHPUnit\Framework\TestCase;

class PackageActionParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testValid()
    {
        $client = $this->createStub(Client::class);

        $clientTransformer = $this->createMock(ClientTransformer::class);
        $clientTransformer->method('transform')->with('42')->willReturn($client);

        $dataProcessor = $this->createDataProcessor([ClientTransformer::class => $clientTransformer]);
        $packageActionParameters = $dataProcessor->process([
            'id' => '42',
            'package' => 'packageName',
        ], PackageActionParameters::class);

        $this->assertSame($client, $packageActionParameters->client);
        $this->assertEquals('packageName', $packageActionParameters->packageName);
    }

    public function testClientMissing()
    {
        $this->assertInvalidFormData([
            'client' => '42',
            'package' => 'packageName',
        ], PackageActionParameters::class);
    }

    public function testPackageMissing()
    {
        $clientTransformer = $this->createStub(ClientTransformer::class);
        $clientTransformer->method('transform')->willReturn($this->createStub(Client::class));

        $this->assertInvalidFormData([
            'id' => '42',
            'packageName' => 'packageName',
        ], PackageActionParameters::class, [ClientTransformer::class => $clientTransformer]);
    }
}
