<?php

namespace Braintacle\Test\Group\Members;

use Braintacle\Client\ClientList\Client;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Client::class)]
#[UsesClass(DateTime::class)]
final class ClientTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function dataProcessorProvider()
    {
        return [
            ['Type'],
            [null],
        ];
    }

    #[DataProvider('dataProcessorProvider')]
    public function testDataProcessor(?string $type)
    {
        $inventoryDate = '2025-10-21 19:53:01';

        $dateTimeTransformer = $this->createMock(DateTimeTransformer::class);
        $dateTimeTransformer
            ->method('transform')
            ->with($inventoryDate, [null, new DateTimeZone('UTC')])
            ->willReturn(new DateTimeImmutable($inventoryDate, new DateTimeZone('UTC')));

        $input = [
            'id' => 42,
            'name' => 'client',
            'userid' => 'user',
            'osname' => 'OS',
            'type' => $type,
            'processors' => 1234,
            'memory' => 5678,
            'lastdate' => $inventoryDate,
        ];
        $client = $this->processData($input, Client::class, [DateTimeTransformer::class => $dateTimeTransformer]);
        $this->assertEquals(42, $client->id);
        $this->assertEquals('client', $client->name);
        $this->assertEquals('user', $client->userName);
        $this->assertEquals('OS', $client->osName);
        $this->assertEquals($type, $client->type);
        $this->assertEquals(1234, $client->cpuClock);
        $this->assertEquals(5678, $client->physicalMemory);
        $this->assertEquals($inventoryDate, $client->inventoryDate->format('Y-m-d H:i:s'));
    }
}
