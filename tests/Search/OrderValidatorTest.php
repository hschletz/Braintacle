<?php

namespace Braintacle\Test\Search;

use Braintacle\Search\OrderValidator;
use Braintacle\Search\SearchResults;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrderValidator::class)]
final class OrderValidatorTest extends TestCase
{
    public static function validOrderProvider()
    {
        return [
            ['_filter'],
            ['Name'],
            ['UserName'],
            ['InventoryDate'],
        ];
    }

    #[DataProvider('validOrderProvider')]
    #[DoesNotPerformAssertions]
    public function testValidOrder(string $order)
    {
        $searchResults = new SearchResults();
        $searchResults->filter = '_filter';
        $searchResults->order = $order;

        $orderValidator = new OrderValidator();
        $orderValidator->process($searchResults);
    }

    public function testInvalidOrder()
    {
        $searchResults = new SearchResults();
        $searchResults->filter = 'Name';
        $searchResults->order = 'invalid';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order column: invalid');

        $orderValidator = new OrderValidator();
        $orderValidator->process($searchResults);
    }
}
