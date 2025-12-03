<?php

namespace Braintacle\Test\Search;

use Braintacle\Search\SearchFilters;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchPageHandler;
use Braintacle\Search\SearchParams;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchPageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class SearchPageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(array $queryParams = []): DOMXPath
    {
        $searchFilters = $this->createStub(SearchFilters::class);
        $searchFilters->method('getFilters')->willReturn([
            'Name' => '_Name',
            'UserName' => '_User name',
        ]);
        $searchFilters->method('getNonTextTypes')->willReturn([
            'CpuClock' => 'number',
            'InventoryDate' => 'date',
        ]);

        $searchParams = new SearchParams();
        $searchParams->filter = $queryParams['filter'] ?? 'unknown';
        $searchParams->search = $queryParams['search'] ?? '';
        $searchParams->operator = SearchOperator::tryFrom($queryParams['operator'] ?? '') ?? SearchOperator::Pattern;
        $searchParams->invert = isset($queryParams['invert']);

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams, SearchParams::class)->willReturn($searchParams);

        $templateEngine = $this->createTemplateEngine();

        $handler = new SearchPageHandler($this->response, $searchFilters, $dataProcessor, $templateEngine);

        $request = $this->request;
        if ($queryParams) {
            $request = $request->withQueryParams($queryParams);
        }

        $response = $handler->handle($request);
        $this->assertResponseStatusCode(200, $response);

        return $this->getXPathFromMessage($response);
    }

    public static function filtersProvider()
    {
        return [
            [
                [],
                '[@value="Name"][normalize-space(text())="_Name"]',
                '[@value="UserName"][normalize-space(text())="_User name"]',
            ],
            [
                ['filter' => 'UserName'],
                '[@value="UserName"][normalize-space(text())="_User name"]',
                '[@value="Name"][normalize-space(text())="_Name"]',
            ],
        ];
    }

    #[DataProvider('filtersProvider')]
    public function testFilters(array $queryParams, string $selectedMatchers, string $unselectedMatchers)
    {
        $xPath = $this->getXpath($queryParams);
        $this->assertXpathMatches($xPath, '//select[@name="filter"]/option[@selected]' . $selectedMatchers);
        $this->assertXpathMatches($xPath, '//select[@name="filter"]/option[not(@selected)]' . $unselectedMatchers);
    }

    public static function searchAttributesProvider()
    {
        return [
            ['UserName', 'text', '[not(@required)]'],
            ['CpuClock', 'number', '[@required]'],
            ['InventoryDate', 'date', '[@required]'],
        ];
    }

    #[DataProvider('searchAttributesProvider')]
    public function testSearchAttributes(string $filter, string $type, string $requiredMatcher)
    {
        $xPath = $this->getXpath(['filter' => $filter]);
        $this->assertXpathMatches($xPath, "//input[@name='search'][@type='$type']" . $requiredMatcher);
    }

    public static function searchValueProvider()
    {
        return [
            [[], ''],
            [['search' => ''], ''],
            [['search' => 'searchValue'], 'searchValue'],
        ];
    }

    #[DataProvider('searchValueProvider')]
    public function testSearchValue(array $queryParams, string $value)
    {
        $xPath = $this->getXpath($queryParams);
        $this->assertXpathMatches($xPath, "//input[@name='search'][@value='$value']");
    }

    public static function operatorsProvider()
    {
        return [
            'default text operator' => [
                [],
                '[@value="like"][starts-with(normalize-space(text()), "_Substring match")]',
                '[@value="eq"][normalize-space(text())="_Exact match"]',
            ],
            'non-default text operator' => [
                ['operator' => 'eq'],
                '[@value="eq"][normalize-space(text())="_Exact match"]',
                '[@value="like"][starts-with(normalize-space(text()), "_Substring match")]',
            ],
            'ordinal operator' => [
                ['filter' => 'CpuClock', 'operator' => 'gt'],
                '[@value="gt"][normalize-space(text())=">"]',
                '[@value="lt"][normalize-space(text())="<"]',
            ]
        ];
    }

    #[DataProvider('operatorsProvider')]
    public function testOperators(array $queryParams, string $selectedMatchers, string $unselectedMatchers)
    {
        $xPath = $this->getXpath($queryParams);
        $this->assertXpathMatches($xPath, '//select[@name="operator"]/option[@selected]' . $selectedMatchers);
        $this->assertXpathMatches($xPath, '//select[@name="operator"]/option[not(@selected)]' . $unselectedMatchers);
    }

    public static function invertProvider()
    {
        return [
            [[], '[not(@checked)]'],
            [['invert' => ''], '[@checked]'],
        ];
    }

    #[DataProvider('invertProvider')]
    public function testInvert(array $queryParams, string $checkedMatcher)
    {
        $xPath = $this->getXpath($queryParams);
        $this->assertXpathMatches($xPath, '//input[@name="invert"]' . $checkedMatcher);
    }
}
