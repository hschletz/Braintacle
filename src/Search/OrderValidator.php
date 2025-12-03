<?php

namespace Braintacle\Search;

use Formotron\PostProcessor;
use InvalidArgumentException;
use Override;

/**
 * Validate "order" field of search results.
 */
final class OrderValidator implements PostProcessor
{
    #[Override]
    public function process(object $dataObject): void
    {
        assert($dataObject instanceof SearchResults);

        if ($dataObject->order != $dataObject->filter && !in_array($dataObject->order, SearchResults::DefaultColumns)) {
            throw new InvalidArgumentException('Invalid order column: ' . $dataObject->order);
        }
    }
}
