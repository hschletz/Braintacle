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
        // entities first. HTML special characters are preserved, which is
        // required for correct HTML parsing.
        return new Document(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    }

    private function assertXpathMatches(Document $document, string $xPath): void
    {
        $this->assertGreaterThan(
            0,
            count(Query::execute($xPath, $document)),
            'Failed asserting that XPath expression matches.'
        );
    }
}
