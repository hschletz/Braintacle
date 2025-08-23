<?php

namespace Braintacle\Test\Search;

use Braintacle\Search\SearchFilterValidator;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchParams;
use Braintacle\Test\DataProcessorTestTrait;
use Braintacle\Transformer\ToBool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchParams::class)]
#[UsesClass(ToBool::class)]
class SearchParamsTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDataProcessing()
    {
        $searchFilterValidator = $this->createMock(SearchFilterValidator::class);
        $searchFilterValidator->expects($this->once())->method('validate')->with('_filter', []);

        $dataProcessor = $this->createDataProcessor([SearchFilterValidator::class => $searchFilterValidator]);
        $searchParams = $dataProcessor->process([
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'invert' => '1',
        ], SearchParams::class);

        $this->assertEquals('_filter', $searchParams->filter);
        $this->assertEquals('_search', $searchParams->search);
        $this->assertEquals(SearchOperator::Equal, $searchParams->operator);
        $this->assertTrue($searchParams->invert);
    }
}
