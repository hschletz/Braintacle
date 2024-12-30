<?php

namespace Braintacle\Test\Dom;

use Braintacle\Dom\Element;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class ElementTest extends TestCase
{
    public function testAppendElement()
    {
        $document = new DOMDocument();
        $element = new Element('parent');
        $document->appendChild($element);
        $child = $element->appendElement('child');
        $child->setAttribute('attr', 'value');
        $this->assertXmlStringEqualsXmlString('<parent><child attr="value" /></parent>', $document->saveXML());
    }

    public function testAppendTextNode()
    {
        $document = new DOMDocument();
        $element = new Element('parent');
        $document->appendChild($element);
        $child = $element->appendTextNode('child', 'text');
        $child->setAttribute('attr', 'value');
        $this->assertXmlStringEqualsXmlString('<parent><child attr="value">text</child></parent>', $document->saveXML());
    }
}
