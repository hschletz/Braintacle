<?php

namespace Library\Test;

use DOMXPath;
use Masterminds\HTML5;

trait DomMatcherTrait
{
    private function createXpath(string $content): DOMXPath
    {
        $html = new HTML5(['disable_html_ns' => true]);
        $this->assertFalse($html->hasErrors());

        return new DOMXPath($html->loadHTML($content));
    }

    private function assertXpathCount(int $count, DOMXPath $xPath, string $expression): void
    {
        $this->assertEquals(
            $count,
            $xPath->query($expression)->count(),
            "Failed asserting that XPath expression matches exactly $count times.",
        );
    }

    private function assertXpathMatches(DOMXPath $xPath, string $expression): void
    {
        $this->assertGreaterThan(
            0,
            $xPath->query($expression)->count(),
            'Failed asserting that XPath expression matches.'
        );
    }

    private function assertNotXpathMatches(DOMXPath $xPath, string $expression): void
    {
        $this->assertEquals(
            0,
            $xPath->query($expression)->count(),
            'Failed asserting that XPath expression does not match.'
        );
    }
}
