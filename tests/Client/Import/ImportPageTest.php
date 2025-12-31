<?php

namespace Braintacle\Test\Client\Import;

use Braintacle\Client\Import\ImportPage;
use Braintacle\FlashMessages;
use Braintacle\Template\TemplateEngine;
use Braintacle\Template\TemplateLoader;
use Braintacle\Test\DomMatcherTrait;
use Braintacle\Test\HttpHandlerTestTrait;
use Braintacle\Test\TemplateTestTrait;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImportPage::class)]
#[UsesClass(TemplateEngine::class)]
#[UsesClass(TemplateLoader::class)]
final class ImportPageTest extends TestCase
{
    use DomMatcherTrait;
    use HttpHandlerTestTrait;
    use TemplateTestTrait;

    private function getXpath(?FlashMessages $flashMessages = null): DOMXPath
    {
        $handler = new ImportPage(
            $this->response,
            $flashMessages ?? $this->createStub(FlashMessages::class),
            $this->createTemplateEngine(),
        );
        $response = $handler->handle($this->request);

        return $this->getXPathFromMessage($response);
    }

    public function testCsrfToken()
    {
        $xPath = $this->getXpath();
        $this->assertXpathMatches($xPath, '//form/input[@type="hidden"][@value="csrf_token"]');
    }

    public function testNoMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with(FlashMessages::Error)->willReturn([]);

        $xPath = $this->getXpath($flashMessages);

        $this->assertNotXpathMatches($xPath, '//p[@class="error"]');
    }

    public function testMessage()
    {
        $flashMessages = $this->createMock(FlashMessages::class);
        $flashMessages->method('get')->with(FlashMessages::Error)->willReturn(['_error']);

        $xPath = $this->getXpath($flashMessages);

        $this->assertXpathMatches($xPath, '//p[@class="error"][text()="_error"]');
    }
}
