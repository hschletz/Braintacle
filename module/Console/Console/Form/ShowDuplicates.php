<?php
/**
 * Form for merging duplicate clients
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

use Zend\Form\Element;

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

        $mergeCustomFields = new Element\Checkbox('mergeCustomFields');
        $mergeCustomFields->setLabel('Merge user supplied information');
        $mergeCustomFields->setChecked($config->defaultMergeCustomFields);
        $this->add($mergeCustomFields);

        $mergeGroups = new Element\Checkbox('mergeGroups');
        $mergeGroups->setLabel('Merge manual group assignments');
        $mergeGroups->setChecked($config->defaultMergeGroups);
        $this->add($mergeGroups);

        $mergePackages = new Element\Checkbox('mergePackages');
        $mergePackages->setLabel('Merge missing package assignments');
        $mergePackages->setChecked($config->defaultMergePackages);
        $this->add($mergePackages);

        $submit = new \Library\Form\Element\Submit('submit');
        $submit->setLabel('Merge selected clients');
        $this->add($submit);

        // Checkboxes for "clients[]" are generated manually, without
        // \Zend\Form\Element. Define an input filter to have them processed.
        $arrayCount = new \Zend\Validator\Callback;
        $arrayCount->setCallback(array($this, 'validateArrayCount'))
                   ->setTranslatorTextDomain('default')
                   ->setMessage(
                       'At least 2 different clients have to be selected',
                       \Zend\Validator\Callback::INVALID_VALUE
                   );
        $inputFilter = new \Zend\InputFilter\InputFilter;
        $inputFilter->add(
            array(
                'name' => 'clients',
                'required' => true,
                'continue_if_empty' => true, // Have empty/missing array processed by callback validator
                'filters' => array(
                    (array($this, 'clientsFilter')),
                ),
                'validators' => array(
                    $arrayCount,
                    new \Zend\Validator\Explode(array('validator' => new \Zend\Validator\Digits)),
                ),
                // Explicit message in case of missing field (no clients selected)
                'error_message' => $arrayCount->getDefaultTranslator()->translate(
                    $arrayCount->getMessageTemplates()[\Zend\Validator\Callback::INVALID_VALUE]
                )
            )
        );
        $this->setInputFilter($inputFilter);
    }

    /** {@inheritdoc} */
    public function getMessages($elementName = null)
    {
        if ($elementName === null) {
            $messages = parent::getMessages();
            $filterMessages = $this->getInputFilter()->getMessages();
            if (isset($filterMessages['clients'])) {
                $messages['clients'] = $filterMessages['clients'];
            }
            return $messages;
        } elseif ($elementName == 'clients') {
            $messages = array();
            $filterMessages = $this->getInputFilter()->getMessages();
            if (isset($filterMessages['clients'])) {
                $messages += $filterMessages['clients'];
            }
            return $messages;
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

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $headers = array(
            'Id' => 'ID',
            'Name' => $view->translate('Name'),
            'NetworkInterface.MacAddress' => $view->translate('MAC address'),
            'Serial' => $view->translate('Serial number'),
            'AssetTag' => $view->translate('Asset tag'),
            'LastContactDate' => $view->translate('Last contact'),
        );
        $renderCriteria = function ($view, $client, $property) {
            $value = $client[$property];
            if ($value === null) {
                // NULL values are never considered for duplicates and cannot be blacklisted.
                return;
            }

            if ($property == 'NetworkInterface.MacAddress') {
                $property = 'MacAddress';
            }
            // Hyperlink to blacklist form
            return $view->htmlTag(
                'a',
                $view->escapeHtml($value),
                array(
                    'href' => $view->consoleUrl(
                        'duplicates',
                        'allow',
                        array(
                            'criteria' => $property,
                            'value' => $value,
                        )
                    ),
                ),
                true
            );
        };
        $renderCallbacks = array(
            'Id' => function ($view, $client) {
                // Display ID and a checkbox. Render checkbox manually because
                // ZF's MultiCheckbox element does not handle duplicate values.
                // $_POST['clients'] will become an array of selected
                // (possibly duplicate) IDs.
                return sprintf(
                    '<input type="checkbox" name="clients[]" value="%d">%d',
                    $client['Id'],
                    $client['Id']
                );
            },
            'Name' => function ($view, $client) {
                // Hyperlink to "customfields" page of given client.
                // This allows for easy review of the information about to be merged.
                return $view->htmlTag(
                    'a',
                    $view->escapeHtml($client['Name']),
                    array(
                        'href' => $view->consoleUrl(
                            'client',
                            'customfields',
                            array('id' => $client['Id'])
                        ),
                    ),
                    true
                );
            },
            'NetworkInterface.MacAddress' => $renderCriteria,
            'Serial' => $renderCriteria,
            'AssetTag' => $renderCriteria,
        );

        $formContent = $view->table(
            $this->getOption('clients'),
            $headers,
            array(
                'order' => $this->getOption('order'),
                'direction' => $this->getOption('direction'),
            ),
            $renderCallbacks
        );

        $formContent .= "<div>\n";
        foreach ($this as $element) {
            $formContent .= $view->formRow($element, 'append') . "\n";
        }
        $formContent .= "</div>\n";

        return $formContent;
    }
}
