<?php

namespace Braintacle\Test\Group\Overview;

use Braintacle\Direction;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Group\Overview\OverviewRequestParameters;
use PHPUnit\Framework\TestCase;

class OverviewRequestParametersTest extends TestCase
{
    public function testDefaults()
    {
        $requestParameters = new OverviewRequestParameters();
        $this->assertEquals(OverviewColumn::Name, $requestParameters->order);
        $this->assertEquals(Direction::Ascending, $requestParameters->direction);
    }
}
