<?php

namespace Braintacle\Test\Duplicates;

use Braintacle\Duplicates\Criterion;
use Braintacle\Duplicates\OverviewHandler;
use Braintacle\FlashMessages;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OverviewHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(DuplicatesManager $duplicatesManager, array $messages): DOMXPath
    {
        $templateEngine = $this->createTemplateEngine();

        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with(FlashMessages::Success)->willReturn($messages);

        $handler = new OverviewHandler($this->response, $duplicatesManager, $templateEngine, $flashMessages);
        $response = $handler->handle($this->request);

        return $this->getXPathFromMessage($response);
    }

    public function testNoDuplicates()
    {
        $duplicatesManager = $this->createMock(DuplicatesManager::class);
        $duplicatesManager->expects($this->exactly(4))->method('count')->willReturn(0);

        $xPath = $this->getXpath($duplicatesManager, []);

        $this->assertXpathMatches($xPath, '//p[normalize-space(text())="_No duplicates present."]');
        $this->assertNotXpathMatches($xPath, '//table');
    }

    public static function showDuplicatesProvider()
    {
        return [
            [Criterion::Name, '_Name'],
            [Criterion::MacAddress, '_MAC Address'],
            [Criterion::Serial, '_Serial number'],
            [Criterion::AssetTag, '_Asset tag'],
        ];
    }

    #[DataProvider('showDuplicatesProvider')]
    public function testShowDuplicates(Criterion $criterion, string $label)
    {
        $count = 4;

        $duplicatesManager = $this->createStub(DuplicatesManager::class);
        $duplicatesManager->method('count')->willReturnCallback(fn (Criterion $c) => $c == $criterion ? $count : 0);

        $xPath = $this->getXpath($duplicatesManager, []);
        $this->assertXpathCount(1, $xPath, '//tr');
        $this->assertXpathMatches($xPath, "//td[1][normalize-space(text())='$label']");
        $this->assertXpathMatches($xPath, "//td[2]/a[@href='manageDuplicates/criterion={$criterion->value}?'][normalize-space(text())='$count']");
        $this->assertNotXpathMatches($xPath, '//p');
    }

    public function testNoMessage()
    {
        $duplicatesManager = $this->createStub(DuplicatesManager::class);
        $duplicatesManager->method('count')->willReturn(0);

        $xPath = $this->getXpath($duplicatesManager, []);

        $this->assertNotXpathMatches($xPath, '//p[@class="success"]');
    }

    public function testMessage()
    {
        $duplicatesManager = $this->createStub(DuplicatesManager::class);
        $duplicatesManager->method('count')->willReturn(0);

        $xPath = $this->getXpath($duplicatesManager, ['success message']);

        $this->assertXpathMatches($xPath, '//p[@class="success"][normalize-space(text())="success message"]');
    }
}
