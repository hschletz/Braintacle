<?php

namespace Braintacle\Dom;

use DOMElement;

/**
 * DOMElement extension with convenience methods.
 */
class Element extends DOMElement
{
    public function appendElement(string $name): self
    {
        $element = $this->appendChild(new self($name));
        assert($element instanceof Element);

        return $element;
    }

    public function appendTextNode(string $name, string $value): self
    {
        $element = $this->appendChild(new self($name));
        assert($element instanceof Element);

        $element->appendChild(
            $this->ownerDocument->createTextNode($value)
        );

        return $element;
    }
}
