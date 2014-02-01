<?php
/**
 * Form for merging duplicate computers
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 * Form for displaying duplicate computers by given criteria and selection of
 * computers/options for merging
 *
 * The form requires the following options to be set:
 *
 * - **config:** \Model\Config instance, required by init(). The factory injects
 *   this automatically.
 * - **computers:** Array of Computer objects to display, required by render().
 * - **order, direction:** Sorting of result table, required by render().
 */
class ShowDuplicates extends \Zend\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
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
        $submit = new Element\Submit('submit');
        $submit->setValue('Merge selected computers');
        $this->add($submit);
    }

    /**
     * Render the form
     *
     * @param \Zend\View\Renderer\PhpRenderer $view
     * @return string HTML form code
     */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $headers = array(
            'Id' => 'ID',
            'Name' => $view->translate('Name'),
            'NetworkInterface.MacAddress' => $view->translate('MAC address'),
            'Serial' => $view->translate('Serial number'),
            'AssetTag' => $view->translate('Asset tag'),
            'LastContactDate' => $view->translate('Last contact'),
        );
        $renderCriteria = function($view, $computer, $property) {
            $value = $computer[$property];
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
            'Id' => function($view, $computer) {
                // Display ID and a checkbox. Render checkbox manually because
                // ZF's MultiCheckbox element does not handle duplicate values.
                // $_POST['computers'] will become an array of selected
                // (possibly duplicate) IDs.
                return sprintf(
                    '<input type="checkbox" name="computers[]" value="%d">%d',
                    $computer['Id'],
                    $computer['Id']
                );
            },
            'Name' => function($view, $computer) {
                // Hyperlink to "userdefined" page of given computer.
                // This allows for easy review of the information about to be merged.
                return $view->htmlTag(
                    'a',
                    $view->escapeHtml($computer['Name']),
                    array(
                        'href' => $view->consoleUrl(
                            'computer',
                            'userdefined',
                            array('id' => $computer['Id'])
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
            $this->getOption('computers'),
            $headers,
            array(
                'order' => $this->getOption('order'),
                'direction' => $this->getOption('direction'),
            ),
            $renderCallbacks
        );

        foreach ($this as $element) {
            $formContent .= $view->htmlTag(
                'p',
                $view->formRow($element, 'append'),
                array('class' => 'textcenter')
            );
        }

        return $view->form()->openTag($this) . "\n" . $formContent . $view->form()->closeTag();
    }
}
