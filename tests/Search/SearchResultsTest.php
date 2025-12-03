<?php

namespace Braintacle\Test\Search;

use Braintacle\Direction;
use Braintacle\Search\OrderValidator;
use Braintacle\Search\SearchFilters;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchResults;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchResults::class)]
class SearchResultsTest extends TestCase
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
        $orderValidator = $this->createMock(OrderValidator::class);
        $orderValidator->expects($this->once())->method('process');

        $searchFilters = $this->createMock(SearchFilters::class);
        $searchFilters->expects($this->once())->method('validate')->with('_filter', []);

        $dataProcessor = $this->createDataProcessor([
            OrderValidator::class => $orderValidator,
            SearchFilters::class => $searchFilters,
        ]);
        $searchParams = $dataProcessor->process($invertParam + [
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'order' => '_order',
            'direction' => 'asc',
        ], SearchResults::class);

        $this->assertEquals('_filter', $searchParams->filter);
        $this->assertEquals('_search', $searchParams->search);
        $this->assertEquals(SearchOperator::Equal, $searchParams->operator);
        $this->assertEquals($invertResult, $searchParams->invert);
        $this->assertEquals('_order', $searchParams->order);
        $this->assertEquals(Direction::Ascending, $searchParams->direction);
    }

    public function testDefaults()
    {
        $orderValidator = $this->createMock(OrderValidator::class);
        $orderValidator->expects($this->once())->method('process');

        $searchFilters = $this->createMock(SearchFilters::class);
        $searchFilters->expects($this->once())->method('validate')->with('_filter', []);

        $dataProcessor = $this->createDataProcessor([
            OrderValidator::class => $orderValidator,
            SearchFilters::class => $searchFilters,
        ]);
        $searchParams = $dataProcessor->process([
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
        ], SearchResults::class);

        $this->assertEquals('InventoryDate', $searchParams->order);
        $this->assertEquals(Direction::Descending, $searchParams->direction);
    }

    public static function toQueryStringProvider()
    {
        return [
            [false, 'filter=_filter&search=_search&operator=eq'],
            [true, 'filter=_filter&search=_search&operator=eq&invert'],
        ];
    }

    #[DataProvider('toQueryStringProvider')]
    public function testToQueryString(bool $invert, string $queryString)
    {
        $searchResults = new SearchResults();
        $searchResults->filter = '_filter';
        $searchResults->search = '_search';
        $searchResults->operator = SearchOperator::Equal;
        $searchResults->invert = $invert;

        $this->assertEquals($queryString, $searchResults->toQueryString());
    }
}
