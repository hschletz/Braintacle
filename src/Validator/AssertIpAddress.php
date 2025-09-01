<?php

namespace Braintacle\Validator;

use Attribute;
use Formotron\Attribute\ValidatorAttribute;
use InvalidArgumentException;
use Laminas\Validator\Ip;
use Override;

/**
 * Validate value as IPv4/IPv6 address.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class AssertIpAddress implements ValidatorAttribute
{
    #[Override]
    public function validate(mixed $value): void
    {
        $validator = new Ip();
        if (!$validator->isValid($value)) {
            throw new InvalidArgumentException(array_values($validator->getMessages())[0] ?? 'Not an IP address');
        }
    }
}
