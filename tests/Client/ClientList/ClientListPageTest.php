<?php

namespace Braintacle\Test\Client\ClientList;

use ArrayIterator;
use Braintacle\Client\ClientList\Client;
use Braintacle\Client\ClientList\ClientListColumn;
use Braintacle\Client\ClientList\ClientListPage;
use Braintacle\Client\Clients;
use Braintacle\Direction;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTimeImmutable;
use DateTimeZone;
use DOMXPath;
use EmptyIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientListPage::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class ClientListPageTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(Clients $clients): DOMXPath
    {
        $dataProcessor = $this->createDataProcessor();

        $templateEngine = $this->createTemplateEngine();

        $clientListPage = new ClientListPage($this->response, $dataProcessor, $clients, $templateEngine);
        $response = $clientListPage->handle($this->request);
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testDefaultOrder()
    {
        $clients = $this->createMock(Clients::class);
        $clients
            ->expects($this->once())
            ->method('getClientList')
            ->with(ClientListColumn::InventoryDate, Direction::Descending)
            ->willReturn(new EmptyIterator());

        $this->getResponseXpath($clients);
    }

    public static function explicitOrderProvider()
    {
        return [
            [ClientListColumn::Name, Direction::Ascending],
            [ClientListColumn::UserName, Direction::Ascending],
            [ClientListColumn::OsName, Direction::Ascending],
            [ClientListColumn::Type, Direction::Ascending],
            [ClientListColumn::CpuClock, Direction::Descending],
            [ClientListColumn::PhysicalMemory, Direction::Descending],
            [ClientListColumn::InventoryDate, Direction::Descending],
        ];
    }

    #[DataProvider('explicitOrderProvider')]
    public function testExplicitOrder(ClientListColumn $order, Direction $direction)
    {
        $clients = $this->createMock(Clients::class);
        $clients
            ->expects($this->once())
            ->method('getClientList')
            ->with($order, $direction)
            ->willReturn(new EmptyIterator());

        $this->request = $this->request->withQueryParams(['order' => $order->name, 'direction' => $direction->value]);

        $this->getResponseXpath($clients);
    }

    public function testEmptyList()
    {
        $clients = $this->createMock(Clients::class);
        $clients
            ->method('getClientList')
            ->with(ClientListColumn::InventoryDate, Direction::Descending)
            ->willReturn(new EmptyIterator());

        $xPath = $this->getResponseXpath($clients);
        $this->assertNotXpathMatches($xPath, '//table');
        $this->assertXpathMatches($xPath, '//p[text()="_Number of clients: 0"]');
    }

    public function testNonEmptyList()
    {
        $client = new Client();
        $client->id = 42;
        $client->name = 'Client1';
        $client->userName = 'User1';
        $client->osName = 'Microsoft Windows';
        $client->type = 'Type1';
        $client->cpuClock = 1234;
        $client->physicalMemory = 5678;
        $client->inventoryDate = new DateTimeImmutable('2025-10-30 20:48:55', new DateTimeZone('UTC'));

        $clients = $this->createMock(Clients::class);
        $clients
            ->method('getClientList')
            ->with(ClientListColumn::InventoryDate, Direction::Descending)
            ->willReturn(new ArrayIterator([$client]));

        $xPath = $this->getResponseXpath($clients);
        $this->assertXpathCount(7, $xPath, '//table/tr[1]/th');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[1]/a[text()="Client1"][@href="showClientGeneral/id=42?"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[2][text()="User1"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[3][text()="Windows"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[4][text()="Type1"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[5][text()="1234"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[6][text()="5678"]');
        $this->assertXpathMatches($xPath, '//table/tr[2]/td[7][text()="30.10.25, 21:48"]');
        $this->assertXpathMatches($xPath, '//p[text()="_Number of clients: 1"]');
    }
}
