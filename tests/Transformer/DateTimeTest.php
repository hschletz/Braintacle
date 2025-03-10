<?php

namespace Braintacle\Test\Transformer;

use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\DateTime;
use Braintacle\Transformer\DateTimeTransformer;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testAttribute()
    {
        $dateTime = new DateTimeImmutable();

        $transformer = $this->createMock(DateTimeTransformer::class);
        $transformer
            ->method('transform')
            ->with('datetime value', ['custom format'])
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
}
