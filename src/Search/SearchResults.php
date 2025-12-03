<?php

namespace Braintacle\Search;

use Braintacle\Direction;
use Formotron\Attribute\PostProcess;
use Formotron\Attribute\UseBackingValue;

/**
 * Search results parameters.
 */
#[PostProcess(OrderValidator::class)]
class SearchResults extends SearchParams
{
    public const DefaultColumns = ['Name', 'UserName', 'InventoryDate'];

    public string $order = 'InventoryDate';

    #[UseBackingValue]
    public Direction $direction = Direction::Descending;

    public function toQueryString(): string
    {
        $queryString = http_build_query([
            'filter' => $this->filter,
            'search' => $this->search,
            'operator' => $this->operator->value,
        ]);
        if ($this->invert) {
            $queryString .= '&invert';
        }

        return $queryString;
    }
}
