<?php

namespace Braintacle\Test\Transformer;

use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateInvalidTimeZoneException;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testExplicitFormat()
    {
        $dateTime = new DateTimeImmutable();

        $transformer = $this->createMock(DateTimeTransformer::class);
        $transformer
            ->method('transform')
            ->with('datetime value', ['custom format', null])
            ->willReturn($dateTime);

        $dataObject = new class
        {
            #[DateTime('custom format')]
            public DateTimeInterface $value;
        };
        $result = $this->processData(
            ['value' => 'datetime value'],
            get_class($dataObject),
            [DateTimeTransformer::class => $transformer]
        );
        $this->assertSame($dateTime, $result->value);
    }

    public function testExplicitTimezone()
    {
        $dateTime = new DateTimeImmutable();

        $transformer = $this->createMock(DateTimeTransformer::class);
        $transformer
            ->method('transform')
            ->with('datetime value', [null, new DateTimeZone('UTC')])
            ->willReturn($dateTime);

        $dataObject = new class
        {
            #[DateTime(timezone: 'UTC')]
            public DateTimeInterface $value;
        };
        $result = $this->processData(
            ['value' => 'datetime value'],
            get_class($dataObject),
            [DateTimeTransformer::class => $transformer]
        );
        $this->assertSame($dateTime, $result->value);
    }

    public function testInvalidTimezone()
    {
        $dataObject = new class
        {
            #[DateTime(timezone: 'invalid')]
            public DateTimeInterface $value;
        };

        $this->expectException(DateInvalidTimeZoneException::class);
        $this->processData(['value' => 'datetime value'], get_class($dataObject));
    }

    public function testDefaults()
    {
        $dateTime = new DateTimeImmutable();

        $transformer = $this->createMock(DateTimeTransformer::class);
        $transformer
            ->method('transform')
            ->with('datetime value', [null, null])
            ->willReturn($dateTime);

        $dataObject = new class
        {
            #[DateTime]
            public DateTimeInterface $value;
        };
        $result = $this->processData(
            ['value' => 'datetime value'],
            get_class($dataObject),
            [DateTimeTransformer::class => $transformer]
        );
        $this->assertSame($dateTime, $result->value);
    }
}
