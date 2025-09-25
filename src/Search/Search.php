<?php

namespace Braintacle\Search;

use Braintacle\Search\SearchParams;
use Laminas\Db\Sql\Select;
use Model\Client\Client;
use Model\Client\ClientManager;

/**
 * Search clients with given criteria.
 */
final class Search
{
    public function __construct(private ClientManager $clientManager) {}

    /**
     * @return iterable<Client>
     */
    public function getClients(SearchParams $searchParams): iterable
    {
        return $this->clientManager->getClients(
            properties: ['Id'],
            filter: $searchParams->filter,
            search: $searchParams->search,
            operators: $searchParams->operator->value,
            invert: $searchParams->invert,
            addSearchColumns: false,
            distinct: true,
            query: true,
        );
    }

    public function getQuery(SearchParams $searchParams): Select
    {
        $select = $this->clientManager->getClients(
            properties: ['Id'],
            filter: $searchParams->filter,
            search: $searchParams->search,
            operators: $searchParams->operator->value,
            invert: $searchParams->invert,
            addSearchColumns: false,
            distinct: true,
            query: false,
        );
        assert($select instanceof Select);

        return $select;
    }
}
