<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Direction;
use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\DuplicatesColumn;
use Braintacle\Duplicates\DuplicatesRequestParameters;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class DuplicatesRequestParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaultValues()
    {
        $dataProcessor = $this->createDataProcessor();
        $duplicatesRequestParameters = $dataProcessor->process(['criterion' => 'name'], DuplicatesRequestParameters::class);

        $this->assertEquals(Criterion::Name, $duplicatesRequestParameters->criterion);
        $this->assertEquals(DuplicatesColumn::Id, $duplicatesRequestParameters->order);
        $this->assertEquals(Direction::Ascending, $duplicatesRequestParameters->direction);
    }

    public function testCriterionMissing()
    {
        $this->assertInvalidFormData([], DuplicatesRequestParameters::class);
    }
}
