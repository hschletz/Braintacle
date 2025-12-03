<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\ExistingGroupFormData;
use Braintacle\Group\Group;
use Braintacle\Group\GroupTransformer;
use Braintacle\Group\Membership;
use Braintacle\Search\SearchFilters;
use Braintacle\Search\SearchOperator;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExistingGroupFormData::class)]
class ExistingGroupFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    public function testDataProcessing()
    {
        $searchFilters = $this->createMock(SearchFilters::class);
        $searchFilters->expects($this->once())->method('validate')->with('_filter', []);

        $group = new Group();
        $groupTransformer = $this->createMock(GroupTransformer::class);
        $groupTransformer->method('transform')->with('_group', [])->willReturn($group);

        $dataProcessor = $this->createDataProcessor([
            SearchFilters::class => $searchFilters,
            GroupTransformer::class => $groupTransformer,
        ]);
        $formData = $dataProcessor->process([
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'invert' => '1',
            'membershipType' => '2',
            'group' => '_group',
        ], ExistingGroupFormData::class);

        $this->assertEquals('_filter', $formData->filter);
        $this->assertEquals('_search', $formData->search);
        $this->assertEquals(SearchOperator::Equal, $formData->operator);
        $this->assertTrue($formData->invert);
        $this->assertEquals(Membership::Never, $formData->membershipType);
        $this->assertSame($group, $formData->group);
    }
}
