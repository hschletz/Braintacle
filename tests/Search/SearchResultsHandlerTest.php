<?php

namespace Braintacle\Test\Search;

use ArrayIterator;
use Braintacle\Direction;
use Braintacle\Search\SearchFilters;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchResults;
use Braintacle\Search\SearchResultsHandler;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTimeImmutable;
use DateTimeInterface;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\Client;
use Model\Client\ClientManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchResultsHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class SearchResultsHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(
        SearchFilters $searchFilters,
        array $clients,
        string $filter,
        SearchOperator $operator,
        bool $invert,
    ): DOMXPath {
        $queryParams = ['foo' => 'bar'];

        $searchResults = $this->createStub(SearchResults::class);
        $searchResults->filter = $filter;
        $searchResults->search = '_search';
        $searchResults->operator = $operator;
        $searchResults->invert = $invert;
        $searchResults->order = $filter;
        $searchResults->direction = Direction::Ascending;
        $searchResults->method('toQueryString')->willReturn('_queryString');

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams)->willReturn($searchResults);

        $clientManager = $this->createMock(ClientManager::class);
        $clientManager->method('getClients')->with(
            SearchResults::DefaultColumns,
            $filter, // order
            'asc',
            $filter,
            '_search',
            $operator->value,
            $invert,
        )->willReturn(new ArrayIterator($clients));

        $templateEngine = $this->createTemplateEngine();

        $handler = new SearchResultsHandler(
            $this->response,
            $dataProcessor,
            $clientManager,
            $searchFilters,
            $templateEngine,
        );
        $response = $handler->handle($this->request->withQueryParams($queryParams));
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public static function defaultColumnsProvider()
    {
        return [
            'equal, default column, no inversion' => [SearchOperator::Equal, 'Name', false],
            'equal, default column, invert' => [SearchOperator::Equal, 'InventoryDate', true],
            'equal, nondefault column, no inversion' => [SearchOperator::Equal, 'CpuClock', false],
            'not equal, default column, no inversion' => [SearchOperator::NotEqual, 'UserName', false],
            'not equal, default column, invert' => [SearchOperator::Pattern, 'Name', true],
        ];
    }

    #[DataProvider('defaultColumnsProvider')]
    public function testDefaultColumns(SearchOperator $operator, string $filter, bool $invert)
    {
        $searchFilters = $this->createMock(SearchFilters::class);
        $searchFilters->expects($this->never())->method('getFilters');
        $searchFilters->expects($this->never())->method('getNonTextTypes');

        $client = $this->createMock(Client::class);
        $client->id = 42;
        $client->name = '_name';
        $client->userName = '_userName';
        $client->inventoryDate = new DateTimeImmutable('2025-11-27 19:38:00');
        $client->expects($this->never())->method('offsetGet');

        $xPath = $this->getXpath($searchFilters, [$client], $filter, $operator, $invert);

        $this->assertXpathCount(3, $xPath, '//table/tr[1]/th');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[1]/a[contains(text(),"_Name")]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[2]/a[contains(text(),"_User")]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[3]/a[contains(text(),"_Last inventory")]');
        $this->assertXpathMatches(
            $xPath,
            '//table/tr[2]/td[1]/a[normalize-space(text()="_name")][@href="showClientGeneral/id=42?"]',
        );
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[2][text()="_userName"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[3][text()="27.11.25, 19:38"]');
    }

    public static function extraColumnsProvider()
    {
        return [
            [SearchOperator::NotEqual, 'OsName', false, '_osName', '_osName'],
            [SearchOperator::Equal, 'CpuClock', true, 1234, '1234'],
            [
                SearchOperator::NotEqual,
                'LastContactDate',
                true,
                new DateTimeImmutable('2025-12-01 18:15:00'),
                '01.12.25',
            ],
        ];
    }

    #[DataProvider('extraColumnsProvider')]
    public function testExtraColumns(
        SearchOperator $operator,
        string $filter,
        bool $invert,
        string | int | DateTimeInterface $extraColumnValue,
        string $extraColumnText,
    ) {
        $searchFilters = $this->createStub(SearchFilters::class);
        $searchFilters->method('getFilters')->willReturn([
            'OsName' => 'Extra Header',
            'CpuClock' => 'Extra Header',
            'LastContactDate' => 'Extra Header',
        ]);
        $searchFilters->method('getNonTextTypes')->willReturn([
            'CpuClock' => 'number',
            'LastContactDate' => 'date',
        ]);

        $client = new Client();
        $client->id = 42;
        $client->name = '_name';
        $client->userName = '_userName';
        $client->inventoryDate = new DateTimeImmutable('2025-11-27 19:38:00');
        $client[$filter] = $extraColumnValue;

        $xPath = $this->getXpath($searchFilters, [$client], $filter, $operator, $invert);

        $this->assertXpathCount(4, $xPath, '//table/tr[1]/th');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[1]/a[contains(text(),"_Name")]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[2]/a[contains(text(),"_User")]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[3]/a[contains(text(),"_Last inventory")]');
        $this->assertXpathMatches($xPath, '//table/tr[1]/th[4]/a[contains(text(),"Extra Header")]');
        $this->assertXpathMatches(
            $xPath,
            '//table/tr[2]/td[1]/a[normalize-space(text()="_name")][@href="showClientGeneral/id=42?"]',
        );
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[2][text()="_userName"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[3][text()="27.11.25, 19:38"]');
        $this->assertXpathMatches($xPath, "//table/tr[2]/td[4][normalize-space(text())='$extraColumnText']");
    }

    public function testCounter()
    {
        $searchFilters = $this->createStub(SearchFilters::class);

        $client1 = new Client();
        $client1->id = 1;
        $client1->name = '_name1';
        $client1->userName = '_userName1';
        $client1->inventoryDate = new DateTimeImmutable('2025-11-27 19:38:01');

        $client2 = new Client();
        $client2->id = 2;
        $client2->name = '_name2';
        $client2->userName = '_userName2';
        $client2->inventoryDate = new DateTimeImmutable('2025-11-27 19:38:02');

        $xPath = $this->getXpath($searchFilters, [$client1, $client2], 'Name', SearchOperator::Equal, false);

        $this->assertXpathCount(3, $xPath, '//tr');
        $this->assertXpathMatches($xPath, '//p[text()="_2 matches"]');
    }

    public function testEmptyResults()
    {
        $searchFilters = $this->createStub(SearchFilters::class);
        $xPath = $this->getXpath($searchFilters, [], 'Name', SearchOperator::Equal, false);

        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertXpathMatches($xPath, '//p[text()="_0 matches"]');
    }

    public function testLinks()
    {
        $searchFilters = $this->createStub(SearchFilters::class);
        $xPath = $this->getXpath($searchFilters, [], 'Name', SearchOperator::Equal, false);

        $this->assertXpathMatches($xPath, '//a[@href="searchPage/??_queryString"][text()="_Edit filter"]');
        $this->assertXpathMatches($xPath, '//a[@href="addGroupPage/??_queryString"][text()="_Save to group"]');
    }
}
