<?php

namespace Braintacle\Test\Client\Registry;

use Braintacle\Client\Registry\RegistryData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegistryData::class)]
final class RegistryDataTest extends TestCase
{
    public function testExportToDom()
    {
        $registryData = new RegistryData();
        $registryData->name = '_name';
        $registryData->data = '_data';

        $this->assertEquals(
            [
                'NAME' => '_name',
                'REGVALUE' => '_data',
            ],
            $registryData->exportToDom(),
        );
    }
}
