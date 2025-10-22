<?php

namespace Braintacle\Test\Group\Members;

use Braintacle\Group\Members\Member;
use Braintacle\Group\Membership;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Member::class)]
#[UsesClass(DateTime::class)]
final class MemberTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDataProcessor()
    {
        $inventoryDate = '2025-10-21 19:53:01';

        $dateTimeTransformer = $this->createMock(DateTimeTransformer::class);
        $dateTimeTransformer
            ->method('transform')
            ->with($inventoryDate, [null, new DateTimeZone('UTC')])
            ->willReturn(new DateTimeImmutable($inventoryDate, new DateTimeZone('UTC')));

        $input = [
            'id' => '42',
            'name' => 'client',
            'userid' => 'user',
            'lastdate' => $inventoryDate,
            'static' => '2',
        ];
        $member = $this->processData($input, Member::class, [DateTimeTransformer::class => $dateTimeTransformer]);
        $this->assertEquals(42, $member->id);
        $this->assertEquals('client', $member->name);
        $this->assertEquals('user', $member->userName);
        $this->assertEquals($inventoryDate, $member->inventoryDate->format('Y-m-d H:i:s'));
        $this->assertEquals(Membership::Never, $member->membership);
    }
}
