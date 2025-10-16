<?php

namespace Braintacle\Test\Group;

use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Group\CacheExpirationTransformer;
use Braintacle\Group\Group;
use Braintacle\KeyMapper\CamelCaseToSnakeCase;
use Braintacle\Test\DatabaseConnection;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Group::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
#[UsesClass(CamelCaseToSnakeCase::class)]
#[UsesClass(DateTime::class)]
#[UsesClass(DateTimeTransformer::class)]
final class GroupTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testAllValues()
    {
        DatabaseConnection::with(function (Connection $connection) {
            $creationDate = '2025-10-10 19:43:26';
            $cacheCreationDate = new DateTimeImmutable('2025-10-10 19:43:27');
            $cacheExpirationDatabase = new DateTimeImmutable('2025-10-10 19:43:28')->getTimestamp();
            $cacheExpirationTransformed = new DateTimeImmutable('2025-10-10 19:43:58');
            $input = [
                'id' => 42,
                'name' => '_name',
                'description' => '_description',
                'creation_date' => $creationDate,
                'dynamic_members_sql' => 'sql',
                'cache_creation_date' => $cacheCreationDate->getTimestamp(),
                'cache_expiration_date' => $cacheExpirationDatabase,
            ];

            $cacheExpiratonTransformer = $this->createMock(CacheExpirationTransformer::class);
            $cacheExpiratonTransformer
                ->method('transform', [])
                ->with($cacheExpirationDatabase)
                ->willReturn($cacheExpirationTransformed);

            $dateTimeTransformer = new DateTimeTransformer($connection);

            $group = $this->processData(
                $input,
                Group::class,
                [
                    CacheExpirationTransformer::class => $cacheExpiratonTransformer,
                    DateTimeTransformer::class => $dateTimeTransformer,
                ]
            );

            $this->assertEquals(42, $group->id);
            $this->assertEquals('_name', $group->name);
            $this->assertEquals('_description', $group->description);
            $this->assertEquals(new DateTimeImmutable($creationDate), $group->creationDate);
            $this->assertEquals('sql', $group->dynamicMembersSql);
            $this->assertEquals($cacheCreationDate, $group->cacheCreationDate);
            $this->assertEquals(30, $group->cacheExpirationDate->getTimestamp() - $cacheExpirationDatabase);
        });
    }

    public function testNullValues()
    {
        DatabaseConnection::with(function (Connection $connection) {
            $input = [
                'id' => 42,
                'name' => 'name',
                'description' => null,
                'creation_date' => '2025-10-10 19:43:26',
                'dynamic_members_sql' => null,
                'cache_creation_date' => 0, // database value cannot be NULL
                'cache_expiration_date' => 0, // database value cannot be NULL
            ];

            $cacheExpiratonTransformer = $this->createMock(CacheExpirationTransformer::class);
            $cacheExpiratonTransformer->method('transform')->with(0)->willReturn(null);

            $dateTimeTransformer = new DateTimeTransformer($connection);

            $group = $this->processData(
                $input,
                Group::class,
                [
                    CacheExpirationTransformer::class => $cacheExpiratonTransformer,
                    DateTimeTransformer::class => $dateTimeTransformer,
                ]
            );

            $this->assertNull($group->description);
            $this->assertNull($group->dynamicMembersSql);
            $this->assertNull($group->cacheCreationDate);
            $this->assertNull($group->cacheCreationDate);
        });
    }
}
