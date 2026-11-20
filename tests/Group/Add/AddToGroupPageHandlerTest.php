<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\AddToGroupPageHandler;
use Braintacle\Group\Group;
use Braintacle\Group\Groups;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchParams;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use Formotron\DataProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AddToGroupPageHandler::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
class AddToGroupPageHandlerTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXPath(array $invertParam): DOMXPath
    {
        $queryParams = $invertParam + [
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
        ];
        $searchParams = new SearchParams();
        $searchParams->filter = '_filter';
        $searchParams->search = '_search';
        $searchParams->operator = SearchOperator::Equal;
        $searchParams->invert = (bool) $invertParam;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams, SearchParams::class)->willReturn($searchParams);

        $group1 = new Group();
        $group1->name = 'group1';
        $group2 = new Group();
        $group2->name = 'group2';

        $groups = $this->createMock(Groups::class);
        $groups->method('getGroups')->with(OverviewColumn::Name)->willReturn([$group1, $group2]);

        $templateEngine = $this->createTemplateEngine();

        $handler = new AddToGroupPageHandler($this->response, $dataProcessor, $groups, $templateEngine);
        $response = $handler->handle($this->request->withQueryParams($queryParams));
        $xPath = $this->getXPathFromMessage($response);

        $this->assertXpathMatches($xPath, '//form[@method="POST"][@action="addGroup/?"][@id="form_addtogroup"]');

        $this->assertXpathMatches(
            $xPath,
            '//form[@id="form_addtogroup"]/input[@name="csrfToken"][@value="csrf_token"]'
        );
        $this->assertXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@name="filter"][@value="_filter"]');
        $this->assertXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@name="search"][@value="_search"]');
        $this->assertXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@name="operator"][@value="eq"]');

        $this->assertXpathMatches($xPath, '//select[@form="form_addtogroup"]/option[1][text()="group1"]');
        $this->assertXpathMatches($xPath, '//select[@form="form_addtogroup"]/option[2][text()="group2"]');

        return $xPath;
    }

    public function testInvert()
    {
        $xPath = $this->getXPath(['invert' => 'on']);
        $this->assertXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@type="hidden"][@name="invert"]');
    }

    public function testNoInvert()
    {
        $xPath = $this->getXPath([]);
        $this->assertNotXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@type="hidden"][@name="invert"]');
    }
}
