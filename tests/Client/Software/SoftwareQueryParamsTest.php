<?php

namespace Braintacle\Test\Client\Software;

use Braintacle\Client\Software\SoftwareColumn;
use Braintacle\Client\Software\SoftwareQueryParams;
use Braintacle\Direction;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class SoftwareQueryParamsTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaultValues()
    {
        $queryParams = $this->processData([], SoftwareQueryParams::class);
        $this->assertEquals(SoftwareColumn::Name, $queryParams->order);
        $this->assertEquals(Direction::Ascending, $queryParams->direction);
    }

    public function testExplicitValues()
    {
        $queryParams = $this->processData(
            [
                'order' => 'version',
                'direction' => 'desc',
            ],
            SoftwareQueryParams::class
        );
        $this->assertEquals(SoftwareColumn::Version, $queryParams->order);
        $this->assertEquals(Direction::Descending, $queryParams->direction);
    }
}
