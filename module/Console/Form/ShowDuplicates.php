<?php

/**
 * Form for merging duplicate clients
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

namespace Console\Form;

use Laminas\Form\Element;

/**
 * Form for displaying duplicate clients by given criteria and selection of
 * clients/options for merging
 *
 * The form requires the following options to be set:
 *
 * - **config:** \Model\Config instance, required by init(). The factory injects
 *   this automatically.
 * - **clients:** Array of Client objects to display, required by render().
 * - **order, direction:** Sorting of result table, required by render().
 */
class ShowDuplicates extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $config = $this->getOption('config');

        $mergeOptions = new Element\MultiCheckbox('mergeOptions');
        $mergeOptions->setValueOptions([
            [
                'value' => \Model\Client\DuplicatesManager::MERGE_CUSTOM_FIELDS,
                'label' => $this->_('Merge user supplied information'),
                'selected' => $config->defaultMergeCustomFields,
            ],
            [
                'value' => \Model\Client\DuplicatesManager::MERGE_CONFIG,
                'label' => $this->_('Merge configuration'),
                'selected' => $config->defaultMergeConfig,
            ],
            [
                'value' => \Model\Client\DuplicatesManager::MERGE_GROUPS,
                'label' => $this->_('Merge manual group assignments'),
                'selected' => $config->defaultMergeGroups,
            ],
            [
                'value' => \Model\Client\DuplicatesManager::MERGE_PACKAGES,
                'label' => $this->_('Merge missing package assignments'),
                'selected' => $config->defaultMergePackages,
            ],
            [
                'value' => \Model\Client\DuplicatesManager::MERGE_PRODUCT_KEY,
                'label' => $this->_('Keep manually entered Windows product key'),
                'selected' => $config->defaultMergeProductKey,
            ],
        ]);
        $this->add($mergeOptions);

        $submit = new \Library\Form\Element\Submit('submit');
        $submit->setLabel('Merge selected clients');
        $this->add($submit);

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
            'error_message' => $arrayCount->getDefaultTranslator()->translate(
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
