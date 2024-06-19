<?php

namespace Console\Validator;

use Laminas\Validator\Csrf;

/**
 * Validate CSRF token.
 */
class CsrfValidator extends Csrf
{
    protected $timeout = null; // Rely on session cleanup

    /**
     * Get current token.
     *
     * @deprecated Call getHash() on injected instance.
     */
    public static function getToken(): string
    {
        $validator = new self();
        return $validator->getHash();
    }
}
