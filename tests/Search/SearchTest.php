<?php

namespace Braintacle\Test\Search;

use Braintacle\Search\Search;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchParams;
use Laminas\Db\Sql\Select;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Search::class)]
final class SearchTest extends TestCase
{
    private function createSearch(ClientManager $clientManager): Search
    {
        return new Search($clientManager);
    }

    public function testGetClients()
    {
        $clients = [$this->createStub(Client::class)];

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())->method('getClients')->with(
            ['Id'],
            null,
            'asc',
            '_filter',
            '_search',
            'eq',
            false,
            false,
            true,
            true,
        )->willReturn($clients);

        $searchParams = new SearchParams();
        $searchParams->filter = '_filter';
        $searchParams->search = '_search';
        $searchParams->operator = SearchOperator::Equal;
        $searchParams->invert = false;

        $search = $this->createSearch($clientManager);
        $this->assertSame($clients, $search->getClients($searchParams));
    }

    public function testGetQuery()
    {
        $select = $this->createStub(Select::class);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->expects($this->once())->method('getClients')->with(
            ['Id'],
            null,
            'asc',
            '_filter',
            '_search',
            'eq',
            true,
            false,
            true,
            false,
        )->willReturn($select);

        $searchParams = new SearchParams();
        $searchParams->filter = '_filter';
        $searchParams->search = '_search';
        $searchParams->operator = SearchOperator::Equal;
        $searchParams->invert = true;

        $search = $this->createSearch($clientManager);
        $this->assertSame($select, $search->getQuery($searchParams));
    }
}
