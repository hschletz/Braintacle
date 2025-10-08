<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\AddToGroupPageHandler;
use Braintacle\Group\Overview\OverviewColumn;
use Braintacle\Search\SearchOperator;
use Braintacle\Search\SearchParams;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use Formotron\DataProcessor;
use Model\Group\Group;
use Model\Group\GroupManager;
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

    public function testHandler()
    {
        $queryParams = [
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'invert' => '1',
        ];
        $searchParams = new SearchParams();
        $searchParams->filter = '_filter';
        $searchParams->search = '_search';
        $searchParams->operator = SearchOperator::Equal;
        $searchParams->invert = true;

        $dataProcessor = $this->createMock(DataProcessor::class);
        $dataProcessor->method('process')->with($queryParams, SearchParams::class)->willReturn($searchParams);

        $group1 = new Group();
        $group1->name = 'group1';
        $group2 = new Group();
        $group2->name = 'group2';

        $groupManager = $this->createMock(GroupManager::class);
        $groupManager->method('getGroups')->with(null, null, OverviewColumn::Name)->willReturn([$group1, $group2]);

        $templateEngine = $this->createTemplateEngine();

        $handler = new AddToGroupPageHandler($this->response, $dataProcessor, $groupManager, $templateEngine);
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
        $this->assertXpathMatches($xPath, '//form[@id="form_addtogroup"]/input[@name="invert"][@value="1"]');

        $this->assertXpathMatches($xPath, '//select[@form="form_addtogroup"]/option[1][text()="group1"]');
        $this->assertXpathMatches($xPath, '//select[@form="form_addtogroup"]/option[2][text()="group2"]');
    }
}
