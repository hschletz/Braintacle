<?php

namespace Braintacle\Test\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\GroupTransformer;
use Braintacle\Group\Members\ExcludedColumn;
use Braintacle\Group\Members\ExcludedRequestParameters;
use Braintacle\Test\DataProcessorTestTrait;
use Model\Group\Group;
use PHPUnit\Framework\TestCase;

class ExcludedRequestParameterTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testInvalid()
    {
        $this->assertInvalidFormData([], ExcludedRequestParameters::class);
    }

    public function testGroup()
    {
        $group = new Group();

        $transformer = $this->createMock(GroupTransformer::class);
        $transformer->method('transform')->with('group_name')->willReturn($group);

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(['name' => 'group_name'], ExcludedRequestParameters::class);

        $this->assertSame($group, $requestParameters->group);
    }

    public function testDefaults()
    {
        $transformer = $this->createStub(GroupTransformer::class);
        $transformer->method('transform')->willReturn(new Group());

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(['name' => ''], ExcludedRequestParameters::class);

        $this->assertEquals(ExcludedColumn::InventoryDate, $requestParameters->order);
        $this->assertEquals(Direction::Descending, $requestParameters->direction);
    }

    public function testExplicitSorting()
    {
        $transformer = $this->createStub(GroupTransformer::class);
        $transformer->method('transform')->willReturn(new Group());

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(
            ['name' => '', 'order' => 'Name', 'direction' => 'asc'],
            ExcludedRequestParameters::class
        );

        $this->assertEquals(ExcludedColumn::Name, $requestParameters->order);
        $this->assertEquals(Direction::Ascending, $requestParameters->direction);
    }
}
