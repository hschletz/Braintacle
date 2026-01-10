<?php

namespace Braintacle\Test\Package\PackageList;

use Braintacle\Direction;
use Braintacle\Package\PackageList\PackageListColumn;
use Braintacle\Package\PackageList\PackageListRequestParameters;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageListRequestParameters::class)]
class PackageListRequestParameterTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaults()
    {
        $dataProcessor = $this->createDataProcessor();
        $requestParameters = $dataProcessor->process([], PackageListRequestParameters::class);

        $this->assertEquals(PackageListColumn::Name, $requestParameters->order);
        $this->assertEquals(Direction::Ascending, $requestParameters->direction);
    }

    public function testExplicitSorting()
    {
        $dataProcessor = $this->createDataProcessor();
        $requestParameters = $dataProcessor->process(
            ['order' => 'Size', 'direction' => 'desc'],
            PackageListRequestParameters::class
        );

        $this->assertEquals(PackageListColumn::Size, $requestParameters->order);
        $this->assertEquals(Direction::Descending, $requestParameters->direction);
    }
}
