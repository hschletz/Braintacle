<?php

namespace Library\Test;

use Laminas\Dom\Document;
use Laminas\Dom\Document\Query;

trait DomMatcherTrait
{
    private function createDocument(string $content): Document
    {
        // Document (and DOMDocument::loadHtml() which is used for HTML parsing)
        // ignores the specified encoding and always interprets content as
        // ISO 8859-1, causing any matches against UTF-8 strings to fail. As a
        // workaround, non-ASCII characters (and only those) are encoded as HTML
        // entities first.
        return new Document(mb_encode_numericentity($content, [0x7f, 0x10ffff, 0, 0x1fffff], 'UTF-8'));
    }

    private function assertXpathMatches(Document $document, string $xPath): void
    {
        $this->assertGreaterThan(
            0,
            count(Query::execute($xPath, $document)),
            'Failed asserting that XPath expression matches.'
        );
    }

    private function assertNotXpathMatches(Document $document, string $xPath): void
    {
        $this->assertEquals(
            0,
            count(Query::execute($xPath, $document)),
            'Failed asserting that XPath expression does not match.'
        );
    }
}
