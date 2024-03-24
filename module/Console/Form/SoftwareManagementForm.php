<?php

namespace Console\Form;

use Console\Validator\CsrfValidator;
use InvalidArgumentException;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Accept/ignore software.
 */
class SoftwareManagementForm
{
    private InputFilter $inputFilter;

    public function __construct()
    {
        $this->inputFilter = new InputFilter();

        $csrf = new Input('csrf');
        $csrf->setRequired(true)
             ->getValidatorChain()
             ->attach(new CsrfValidator(), true);
        $this->inputFilter->add($csrf);
    }

    /**
     * @return array<array-key, array<array-key, string|array>>
     */
    public function getValidationMessages(array $formData): array
    {
        $this->inputFilter->setData($formData);
        if ($this->inputFilter->isValid()) {
            $software = $formData['software'] ?? [];
            if (
                isset($formData['accept']) && isset($formData['ignore']) ||
                !isset($formData['accept']) && !isset($formData['ignore']) ||
                !is_array($software)
            ) {
                throw new InvalidArgumentException('Invalid form data');
            }
        }

        return $this->inputFilter->getMessages();
    }
}
