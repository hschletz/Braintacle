<?php

namespace Braintacle\Test\Software;

use Braintacle\Http\OrderHelper;
use Braintacle\Software\SoftwarePageHandler;
use Braintacle\Template\TemplateEngine;
use Braintacle\Test\HttpHandlerTestTrait;
use Model\SoftwareManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class SoftwarePageHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;

    private function getResponseForFilter(string $filter): ResponseInterface
    {
        $orderHelper = $this->createStub(OrderHelper::class);
        $orderHelper->method('__invoke')->willReturn(['name', 'asc']);
        $handler = new SoftwarePageHandler(
            $this->response,
            $orderHelper,
            $this->createTemplateEngine(),
            $this->createStub(SoftwareManager::class),
        );

        return $handler->handle($this->request->withQueryParams(['filter' => $filter]));
    }

    public static function parameterProvider()
    {
        return [
            [['filter' => 'filterName'], 'filterName'],
            [[], 'accepted'],
        ];
    }

    #[DataProvider('parameterProvider')]
    public function testParameterEvaluation($queryParams, $expectedFilter)
    {
        $orderHelper = $this->createMock(OrderHelper::class);
        $orderHelper->method('__invoke')->with($queryParams, 'name')->willReturn(['_order', '_direction']);

        $filters = [
            'Os' => 'windows',
            'Status' => $expectedFilter,
        ];
        $softwareManager = $this->createMock(SoftwareManager::class);
        $softwareManager
            ->expects($this->once())
            ->method('getSoftware')
            ->with($filters, '_order', '_direction')
            ->willReturn([]);

        $handler = new SoftwarePageHandler(
            $this->response,
            $orderHelper,
            $this->createStub(TemplateEngine::class),
            $softwareManager,
        );
        $handler->handle($this->request->withQueryParams($queryParams));
    }

    public static function filterProvider()
    {
        return [
            ['accepted'],
            ['ignored'],
            ['new'],
            ['all'],
        ];
    }

    #[DataProvider('filterProvider')]
    public function testFilterSelect(string $filter)
    {
        $response = $this->getResponseForFilter($filter);
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query("//option[@value='$filter'][@selected]"));
        $this->assertCount(1, $xPath->query("//option[@selected]"));
    }

    public function testInvalidFilter()
    {
        $response = $this->getResponseForFilter('invalid');
        $xPath = $this->getXPathFromMessage($response);
        $this->assertEmpty($xPath->query('//option[@selected]'));
    }

    public function testFilterButtonsAccepted()
    {
        $response = $this->getResponseForFilter('accepted');
        $xPath = $this->getXPathFromMessage($response);
        $this->assertEmpty($xPath->query('//button[@name="action"][@value="accept"]'));
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="ignore"]'));
    }

    public function testFilterButtonsIgnored()
    {
        $response = $this->getResponseForFilter('ignored');
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="accept"]'));
        $this->assertEmpty($xPath->query('//button[@name="action"][@value="ignore"]'));
    }

    public function testFilterButtonsNew()
    {
        $response = $this->getResponseForFilter('new');
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="accept"]'));
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="ignore"]'));
    }

    public function testFilterButtonsAll()
    {
        $response = $this->getResponseForFilter('all');
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="accept"]'));
        $this->assertCount(1, $xPath->query('//button[@name="action"][@value="ignore"]'));
    }

    public function testSoftwareList()
    {
        $orderHelper = $this->createStub(OrderHelper::class);
        $orderHelper->method('__invoke')->willReturn(['name', 'asc']);

        $softwareManager = $this->createStub(SoftwareManager::class);
        $softwareManager->method('getSoftware')->willReturn([
            ['name' => 'name', 'num_clients' => 1],
            ['name' => "<name>", 'num_clients' => 2],
        ]);
        $handler = new SoftwarePageHandler(
            $this->response,
            $orderHelper,
            $this->createTemplateEngine(),
            $softwareManager,
        );
        $response = $handler->handle($this->request);
        $xPath = $this->getXPathFromMessage($response);
        $this->assertCount(2, $xPath->query('//tr[td]'));

        $this->assertCount(1, $xPath->query('//tr[2]/td[1]/input[@value="name"]'));
        $this->assertCount(1, $xPath->query('//tr[2]/td[2][text()="name"]'));
        $this->assertCount(1, $xPath->query('//tr[2]/td[3]/a[normalize-space(text())="1"][contains(@href, "search=name")]'));

        $this->assertCount(1, $xPath->query('//tr[3]/td[1]/input[@value="<name>"]'));
        $this->assertCount(1, $xPath->query('//tr[3]/td[2][text()="<name>"]'));
        $this->assertCount(1, $xPath->query('//tr[3]/td[3]/a[normalize-space(text())="2"][contains(@href, "search=%3Cname%3E")]'));
    }
}
