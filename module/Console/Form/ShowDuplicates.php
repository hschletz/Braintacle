<?php

namespace Console\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;
use Laminas\Translator\TranslatorInterface;
use Model\Client\DuplicatesManager;

/**
 * Validate form data for merging duplicate clients.
 *
 * @extends Form<array>
 */
class ShowDuplicates extends Form
{
    public function __construct(private TranslatorInterface $translator)
    {
        parent::__construct();
        $this->init();
    }

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        // valueOptions don't have a label because labels are provided by the
        // form template.
        $mergeOptions = new Element\MultiCheckbox('mergeOptions');
        $mergeOptions->setValueOptions([
            DuplicatesManager::MERGE_CUSTOM_FIELDS => '',
            DuplicatesManager::MERGE_CONFIG => '',
            DuplicatesManager::MERGE_GROUPS => '',
            DuplicatesManager::MERGE_PACKAGES => '',
            DuplicatesManager::MERGE_PRODUCT_KEY => '',
        ]);
        $this->add($mergeOptions);

        // Checkboxes for "clients[]" are generated manually, without
        // \Laminas\Form\Element. Define an input filter to have them processed.
        $arrayCount = new \Laminas\Validator\Callback();
        $arrayCount->setCallback(array($this, 'validateArrayCount'))
            ->setMessage(
                'At least 2 different clients have to be selected',
                \Laminas\Validator\Callback::INVALID_VALUE
            );
        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add([
            'name' => 'clients',
            'required' => true,
            'continue_if_empty' => true, // Have empty/missing array processed by callback validator
            'filters' => [[$this, 'clientsFilter']],
            'validators' => [
                $arrayCount,
                new \Laminas\Validator\Explode(['validator' => new \Laminas\Validator\Digits()]),
            ],
            // Explicit message in case of missing field (no clients selected)
            'error_message' => $this->translator->translate(
                $arrayCount->getMessageTemplates()[\Laminas\Validator\Callback::INVALID_VALUE]
            )
        ]);
        $inputFilter->add([
            'name' => 'mergeOptions',
            'required' => false, // Allow unchecking all options
            'filters' => [['name' => 'Library\Filter\EmptyArray']],
        ]);
        $this->setInputFilter($inputFilter);
    }

    public function getMessages(?string $elementName = null): array
    {
        if ($elementName == 'clients') {
            // Parent implementation would check for a form element named
            // 'clients' which does not exist. Bypass parent and return message
            // directly.
            if (isset($this->messages['clients'])) {
                return $this->messages['clients'];
            } else {
                return [];
            }
        } else {
            return parent::getMessages($elementName);
        }
    }

    /**
     * Filter callback for "clients" input
     *
     * @internal
     * @param mixed $clients
     * @return array Unique input values
     * @throws \InvalidArgumentException if $clients is not array|null
     */
    public function clientsFilter($clients)
    {
        if (is_array($clients)) {
            return array_unique($clients);
        } elseif ($clients === null) {
            return array();
        } else {
            throw new \InvalidArgumentException('Invalid input for "clients": ' . $clients);
        }
    }

    /**
     * Validator callback for "clients" input
     *
     * @internal
     * @param array $array
     * @return bool TRUE if $array has at least 2 members
     */
    public function validateArrayCount(array $array)
    {
        return count($array) >= 2;
    }
}
