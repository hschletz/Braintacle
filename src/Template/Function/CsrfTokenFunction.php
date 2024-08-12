<?php

namespace Braintacle\Template\Function;

use Laminas\Validator\Csrf;

/**
 * Retrieve CSRF protection token.
 */
class CsrfTokenFunction
{
    public function __construct(private Csrf $csrfValidator)
    {
    }

    public function __invoke(): string
    {
        return $this->csrfValidator->getHash();
    }
}
