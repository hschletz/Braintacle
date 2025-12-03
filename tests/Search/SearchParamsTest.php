<?php

namespace Braintacle\Test\Search;

use Braintacle\Search\SearchFilters;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchParams;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchParams::class)]
class SearchParamsTest extends TestCase
{
    use DataProcessorTestTrait;

    public static function dataProcessingProvider()
    {
        return [
            [[], false],
            [['invert' => ''], true],
        ];
    }

    #[DataProvider('dataProcessingProvider')]
    public function testDataProcessing(array $invertParam, bool $invertResult)
    {
        $searchFilters = $this->createMock(SearchFilters::class);
        $searchFilters->expects($this->once())->method('validate')->with('_filter', []);

        $dataProcessor = $this->createDataProcessor([SearchFilters::class => $searchFilters]);
        $searchParams = $dataProcessor->process($invertParam + [
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
        ], SearchParams::class);

        $this->assertEquals('_filter', $searchParams->filter);
        $this->assertEquals('_search', $searchParams->search);
        $this->assertEquals(SearchOperator::Equal, $searchParams->operator);
        $this->assertEquals($invertResult, $searchParams->invert);
    }
}
