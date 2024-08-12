<?php

namespace Braintacle;

use Formotron\AssertionFailedException;
use Formotron\PreProcessor;
use Laminas\Validator\Csrf;

/**
 * Validate and remove csrfToken element from form data.
 */
class CsrfProcessor implements PreProcessor
{
    public function __construct(private Csrf $csrfValidator)
    {
    }

    public function process(array $formData): array
    {
        $token = $formData['csrfToken'] ?? throw new AssertionFailedException('csrfToken not set');
        if (!$this->csrfValidator->isValid($token)) {
            $messages = $this->csrfValidator->getMessages();
            $message = array_shift($messages);
            throw new AssertionFailedException($message);
        }

        unset($formData['csrfToken']);
        return $formData;
    }
}
