<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Duplicates\AllowDuplicatesRequestParameters;
use Braintacle\Duplicates\Criterion;
use Braintacle\Test\DataProcessorTestTrait;
use Formotron\AssertionFailedException;
use PHPUnit\Framework\TestCase;

class AllowDuplicatesRequestParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testExplicitValues()
    {
        $requestParameters = $this->processData(
            ['criterion' => 'mac_address', 'value' => '02:00:00:00:01:01'],
            AllowDuplicatesRequestParameters::class,
        );
        $this->assertEquals(Criterion::MacAddress, $requestParameters->criterion);
        $this->assertEquals('02:00:00:00:01:01', $requestParameters->value);
    }

    public function testCriterionMissing()
    {
        $this->expectException(AssertionFailedException::class);
        $this->processData(
            ['value' => '02:00:00:00:01:01'],
            AllowDuplicatesRequestParameters::class,
        );
    }

    public function testValueMissing()
    {
        $this->expectException(AssertionFailedException::class);
        $this->processData(
            ['criterion' => Criterion::MacAddress],
            AllowDuplicatesRequestParameters::class,
        );
    }
}
