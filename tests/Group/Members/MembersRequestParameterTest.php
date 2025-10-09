<?php

namespace Braintacle\Test\Group\Members;

use Braintacle\Direction;
use Braintacle\Group\Group;
use Braintacle\Group\GroupTransformer;
use Braintacle\Group\Members\MembersColumn;
use Braintacle\Group\Members\MembersRequestParameters;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class MembersRequestParameterTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testInvalid()
    {
        $this->assertInvalidFormData([], MembersRequestParameters::class);
    }

    public function testGroup()
    {
        $group = new Group();

        $transformer = $this->createMock(GroupTransformer::class);
        $transformer->method('transform')->with('group_name')->willReturn($group);

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(['name' => 'group_name'], MembersRequestParameters::class);

        $this->assertSame($group, $requestParameters->group);
    }

    public function testDefaults()
    {
        $transformer = $this->createStub(GroupTransformer::class);
        $transformer->method('transform')->willReturn(new Group());

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(['name' => ''], MembersRequestParameters::class);

        $this->assertEquals(MembersColumn::InventoryDate, $requestParameters->order);
        $this->assertEquals(Direction::Descending, $requestParameters->direction);
    }

    public function testExplicitSorting()
    {
        $transformer = $this->createStub(GroupTransformer::class);
        $transformer->method('transform')->willReturn(new Group());

        $dataProcessor = $this->createDataProcessor([GroupTransformer::class => $transformer]);
        $requestParameters = $dataProcessor->process(
            ['name' => '', 'order' => 'Name', 'direction' => 'asc'],
            MembersRequestParameters::class
        );

        $this->assertEquals(MembersColumn::Name, $requestParameters->order);
        $this->assertEquals(Direction::Ascending, $requestParameters->direction);
    }
}
