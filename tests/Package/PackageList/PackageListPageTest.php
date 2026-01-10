<?php

namespace Braintacle\Test\Package\PackageList;

use ArrayIterator;
use Braintacle\Legacy\Plugin\FlashMessenger;
use Braintacle\Package\PackageList\PackageListPage;
use Braintacle\Package\PackageList\PackageListRequestParameters;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Package\Package;
use Model\Package\PackageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PackageListPage::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class PackageListPageTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(array $packages = [], ?FlashMessenger $flashMessenger = null): DOMXPath
    {
        $queryParams = ['order' => 'Name', 'direction' => 'asc'];

        $packageManager = $this->createStub(PackageManager::class);
        $packageManager->method('getPackages')->willReturn(new ArrayIterator($packages));

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor
            ->method('process')
            ->with($queryParams, PackageListRequestParameters::class)
            ->willReturn(new PackageListRequestParameters());

        if (!$flashMessenger) {
            $flashMessenger = $this->createMock(FlashMessenger::class);
            $flashMessenger->method('getMessagesFromNamespace')->willReturn([]);
        }

        $templateEngine = $this->createTemplateEngine();

        $handler = new PackageListPage(
            $this->response,
            $dataProcessor,
            $packageManager,
            $flashMessenger,
            $templateEngine,
        );
        $response = $handler->handle($this->request->withQueryParams($queryParams));
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testPackageList()
    {
        $package1 = new Package();
        $package1->name = 'name1';
        $package1->comment = 'comment1';
        $package1->timestamp = new DateTime('2014-03-29 20:03:45');
        $package1->size = 12345678;
        $package1->platform = 'platform';
        $package1->numPending = 1;
        $package1->numRunning = 2;
        $package1->numSuccess = 3;
        $package1->numError = 4;

        $package2 = new Package();
        $package2->name = 'name2';
        $package2->comment = null;
        $package2->timestamp = new DateTime('2014-03-29 20:15:43');
        $package2->size = 87654321;
        $package2->platform = 'platform';
        $package2->numPending = 0;
        $package2->numRunning = 0;
        $package2->numSuccess = 0;
        $package2->numError = 0;

        $xPath = $this->getXpath(packages: [$package1, $package2]);

        $this->assertXpathMatches($xPath, '//td/a[@href="packageUpdatePage/?name=name1"][@title="comment1"]');
        $this->assertXpathMatches($xPath, '//td/a[@href="packageUpdatePage/?name=name2"][not(@title)]');
        $this->assertXpathMatches($xPath, '//td[text()="29.03.2014, 20:03"]');
        $this->assertXpathMatches($xPath, '//td[@class="textright"][text()="12 MB"]');
        $this->assertXpathCount(2, $xPath, '//td[text()="Platform"]');

        // Hyperlinks and classes for Num* columns
        $query = '//td[@class="textright"]/a[@href="clientIndex/' .
            '?columns=Name%%2CUserName%%2CLastContactDate%%2CInventoryDate&jumpto=software&' .
            'filter=%s&search=%s"][@class="%s"][text()="%s"]';
        $this->assertXpathMatches($xPath, sprintf($query, 'PackagePending', 'name1', 'package_pending', '1'));
        $this->assertXpathMatches($xPath, sprintf($query, 'PackageRunning', 'name1', 'package_running', '2'));
        $this->assertXpathMatches($xPath, sprintf($query, 'PackageSuccess', 'name1', 'package_success', '3'));
        $this->assertXpathMatches($xPath, sprintf($query, 'PackageError', 'name1', 'package_error', '4'));

        // Num* columns with '0' content
        $this->assertXpathCount(4, $xPath, '//td[@class="textright"][normalize-space(text())="0"]');

        $this->assertXpathMatches(
            $xPath,
            '//td/a[@href="packageDeletePage/?name=name1"][normalize-space(text())="_Delete"]',
        );

        // No flash messages
        $this->assertNotXpathMatches($xPath, '//ul[@class="error"]');
        $this->assertNotXpathMatches($xPath, '//ul[@class="success"]');
    }

    public function testFlashMessages()
    {
        $flashMessenger = $this->createStub(FlashMessenger::class);
        $flashMessenger->method('getMessagesFromNamespace')->willReturnMap([
            ['error', ['errorMessage']],
            ['success', ['successMessage']],
        ]);

        $xPath = $this->getXpath(flashMessenger: $flashMessenger);
        $this->assertXpathCount(1, $xPath, '//ul[@class="error"]/li[text()="errorMessage"]');
        $this->assertXpathCount(1, $xPath, '//ul[@class="success"]/li[text()="successMessage"]');
    }
}
