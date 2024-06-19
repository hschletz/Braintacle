<?php

namespace Braintacle\Template\Function;

use Console\Validator\CsrfValidator;

/**
 * Retrieve CSRF protection token.
 */
class CsrfTokenFunction
{
    public function __construct(private CsrfValidator $csrfValidator)
    {
    }

    public function __invoke(): string
    {
        return $this->csrfValidator->getHash();
    }
}
