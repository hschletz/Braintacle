<?php

namespace Braintacle\Dom;

/**
 * Provide mapping between a data object and an XML document.
 */
interface DomMapper
{
    /**
     * Export object content to DOM.
     *
     * @return non-empty-array<string, string> Tag name => content
     */
    public function exportToDom(): array;
}
