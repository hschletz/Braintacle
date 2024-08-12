<?php

namespace Braintacle\Validator;

use Formotron\Validator;

/**
 * Validate string list (list<string>)
 */
class IsStringList implements Validator
{
    public function getValidationErrors(mixed $value): array
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
