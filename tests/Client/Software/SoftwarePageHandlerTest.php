<?php

namespace Braintacle\Test\Client\Software;

use Braintacle\Client\ClientRequestParameters;
use Braintacle\Client\Software\SoftwarePageHandler;
use Braintacle\Client\Software\SoftwareQueryParams;
use Braintacle\Http\RouteHelper;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DateTime;
use DOMXPath;
use Formotron\DataProcessor;
use Model\Client\AndroidInstallation;
use Model\Client\Client;
use Model\Client\Item\Software;
use Model\Client\WindowsInstallation;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SoftwarePageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class SoftwarePageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getResponseXpath(client $client, int $displayBlacklistedSoftware): DOMXPath
    {
        $client->id = 42;
        $client->name = 'test';

        $routeArguments = ['id' => '42'];
        $queryParams = [];

        $routeHelper = $this->createStub(RouteHelper::class);
        $routeHelper->method('getRouteArguments')->willReturn($routeArguments);

        $clientRequestParameters = new ClientRequestParameters();
        $clientRequestParameters->client = $client;

        $dataProcessor = $this->createStub(DataProcessor::class);
        $dataProcessor->method('process')->willReturnMap([
            [$routeArguments, ClientRequestParameters::class, $clientRequestParameters],
            [$queryParams, SoftwareQueryParams::class, new SoftwareQueryParams()],
        ]);

        $config = $this->createMock(Config::class);
        $config->method('__get')->with('displayBlacklistedSoftware')->willReturn($displayBlacklistedSoftware);

        $templateEngine = $this->createTemplateEngine();

        $handler = new SoftwarePageHandler($this->response, $routeHelper, $dataProcessor, $config, $templateEngine);
        $response = $handler->handle($this->request);
        // echo $this->getMessageContent($response);

        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public function testWindows()
    {
        $software1 = new Software();
        $software1->name = 'name1';
        $software1->version = 'version1';
        $software1->comment = '';
        $software1->publisher = 'publisher1';
        $software1->installLocation = 'location1';
        $software1->architecture = 32;

        $software2 = new Software();
        $software2->name = 'name2';
        $software2->version = 'version2';
        $software2->comment = '';
        $software2->publisher = 'publisher2';
        $software2->installLocation = 'location2';
        $software2->architecture = null;

        $windows = $this->createStub(WindowsInstallation::class);

        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([['windows', $windows]]);
        $client->method('getItems')->with('Software')->willReturn([$software1, $software2]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Version"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Publisher"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Location"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Architecture"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Size"]');
        $this->assertXpathMatches($xpath, "//tr[2]/td[5][normalize-space(text())='32\xC2\xA0Bit']");
        $this->assertXpathMatches($xpath, '//tr[3]/td[5][normalize-space(text())=""]');
    }

    public function testUnix()
    {
        $software1 = new Software();
        $software1->name = 'name1';
        $software1->version = 'version1';
        $software1->size = 23;

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('Software')->willReturn([$software1]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Version"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Publisher"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Location"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Architecture"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Size"]');
        $this->assertXpathMatches($xpath, '//tr[2]/td[3][@class="textright"][normalize-space(text())="23 kB"]');
    }

    public function testUnixWithNullSize()
    {
        $software1 = new Software();
        $software1->name = 'name1';
        $software1->version = 'version1';
        $software1->size = null;

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('Software')->willReturn([$software1]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//tr[2]/td[3][@class="textright"][normalize-space(text())=""]');
    }

    public function testAndroid()
    {
        $software1 = new Software();
        $software1->name = 'name1';
        $software1->version = 'version1';
        $software1->publisher = 'publisher1';
        $software1->installLocation = 'location1';

        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([
            ['windows', null],
            ['android', $this->createStub(AndroidInstallation::class)],
        ]);
        $client->method('getItems')->with('Software')->willReturn([$software1]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Version"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Publisher"]');
        $this->assertXpathMatches($xpath, '//th/a[normalize-space(text())="_Location"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Architecture"]');
        $this->assertNotXpathMatches($xpath, '//th/a[normalize-space(text())="_Size"]');
    }

    public function testComments()
    {
        $software1 = new Software();
        $software1->name = 'name1';
        $software1->comment = 'comment1';
        $software1->version = 'version1';
        $software1->size = 0;

        $software2 = new Software();
        $software2->name = 'name2';
        $software2->comment = '';
        $software2->version = 'version2';
        $software2->size = 0;

        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('Software')->willReturn([$software1, $software2]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//tr[2]/td[1][@title="comment1"]');
        $this->assertNotXpathMatches($xpath, '//tr[3]/td[1][title]');
    }

    public function testDuplicates()
    {
        $software1a = new Software();
        $software1a->name = 'name';
        $software1a->version = 'version1';
        $software1a->comment = '';
        $software1a->publisher = '';
        $software1a->installLocation = '';
        $software1a->isHotfix = null;
        $software1a->guid = '';
        $software1a->language = '';
        $software1a->installationDate = new DateTime('2015-05-25');
        $software1a->architecture = null;

        $software1b = clone $software1a;

        $software2 = new Software();
        $software2->name = 'name';
        $software2->version = 'version2';
        $software2->comment = '';
        $software2->publisher = '';
        $software2->installLocation = '';
        $software2->isHotfix = null;
        $software2->guid = '';
        $software2->language = '';
        $software2->installationDate = new DateTime('2015-05-25');
        $software2->architecture = null;

        $client = $this->createMock(Client::class);
        $client->method('__get')->willReturnMap([['windows', $this->createStub(WindowsInstallation::class)]]);
        $client->method('getItems')->with('Software')->willReturn([$software1a, $software2, $software1b]);

        $xpath = $this->getResponseXpath($client, 0);

        $this->assertXpathMatches($xpath, '//tr[2]/td[1]/span[@class="duplicate"][text()="(2)"]');
        $this->assertNotXpathMatches($xpath, '//tr[3]/td[1]/span');
    }

    public function testHideBlacklisted()
    {
        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('Software', 'name', 'asc', ['Software.NotIgnored' => null])->willReturn([]);

        $this->getResponseXpath($client, 0);
    }

    public function testShowBlacklisted()
    {
        $client = $this->createMock(Client::class);
        $client->method('getItems')->with('Software', 'name', 'asc', [])->willReturn([]);

        $this->getResponseXpath($client, 1);
    }
}
