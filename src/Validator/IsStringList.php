<?php

namespace Braintacle\Validator;

use Formotron\Validator;
use Override;

/**
 * Validate string list (list<string>)
 */
class IsStringList implements Validator
{
    #[Override]
    public function getValidationErrors(mixed $value, array $args): array
    {
        $messages = [];
        if (!array_is_list($value)) {
            $messages[] = 'Input array is not a list';
        }
        foreach ($value as $element) {
            if (!is_string($element)) {
                $messages[] = 'Input array contains non-string elements';
                break;
            }
        }

        return $messages;
    }
}
