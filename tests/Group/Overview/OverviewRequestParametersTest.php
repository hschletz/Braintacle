<?php

namespace Braintacle\Test\Group\Overview;

use Braintacle\Direction;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Group\Overview\OverviewRequestParameters;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class OverviewRequestParametersTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaults()
    {
        $requestParameters = $this->processData([], OverviewRequestParameters::class);
        $this->assertEquals(OverviewColumn::Name, $requestParameters->order);
        $this->assertEquals(Direction::Ascending, $requestParameters->direction);
    }

    public function testExplicitValues()
    {
        $formData = $this->processData(
            [
                'order' => 'Description',
                'direction' => 'desc',
            ],
            OverviewRequestParameters::class
        );
        $this->assertEquals(OverviewColumn::Description, $formData->order);
        $this->assertEquals(Direction::Descending, $formData->direction);
    }
}
