<?php

namespace Braintacle\Test\Software;

use Braintacle\Direction;
use Braintacle\Software\SoftwareFilter;
use Braintacle\Software\SoftwarePageColumn;
use Braintacle\Software\SoftwarePageHandler;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use Formotron\DataProcessor;
use Model\SoftwareManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(SoftwarePageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class SoftwarePageHandlerTest extends TestCase
{
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseForFilter(string $filter): ResponseInterface
    {
        $dataProcessor = new DataProcessor($this->createStub(ContainerInterface::class));

        $handler = new SoftwarePageHandler(
            $this->response,
            $dataProcessor,
            $this->createTemplateEngine(),
            $this->createStub(SoftwareManager::class),
        );

        return $handler->handle($this->request->withQueryParams(['filter' => $filter]));
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
        $dataProcessor = new DataProcessor($this->createStub(ContainerInterface::class));

        $softwareManager = $this->createMock(SoftwareManager::class);
        $softwareManager
            ->method('getSoftware')
            ->with(SoftwareFilter::Accepted, SoftwarePageColumn::Name, Direction::Ascending)
            ->willReturn([
                ['name' => 'name', 'num_clients' => 1],
                ['name' => "<name>", 'num_clients' => 2],
            ]);
        $handler = new SoftwarePageHandler(
            $this->response,
            $dataProcessor,
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
