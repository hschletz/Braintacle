<?php
/**
 * Form for assigning packages to a client or group
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
 */

namespace Console\Form\Package;

/**
 * Form for assigning packages to a client or group
 *
 * Available packages are set via setPackages() or setData(). Each package has a
 * checkbox (with the same name as the package) in the "Packages" fieldset.
 */
class Assign extends \Console\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Assign');
        $this->add($submit);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        if (isset($data['Packages'])) {
            $packages = array_keys($data['Packages']);
        } else {
            $packages = array();
        }
        $this->setPackages($packages);
        return parent::setData($data);
    }

    /**
     * Set available packages
     *
     * The "Packages" fieldset is (re)created with checkboxes for each package.
     *
     * @param string[] $packages Package names
     */
    public function setPackages(array $packages)
    {
        if ($this->has('Packages')) {
            $this->remove('Packages');
        }
        $fieldset= new \Zend\Form\Fieldset('Packages');
        $this->add($fieldset);

        foreach ($packages as $package) {
            $element = new \Zend\Form\Element\Checkbox($package);
            $element->setLabel($package);
            $fieldset->add($element);
        }
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $output = '';
        if ($fieldset->has('Packages')) {
            $packages = $fieldset->get('Packages');
            if ($packages->count()) {
                $formRow = $view->plugin('FormRow');
                $translatorEnabled = $formRow->isTranslatorEnabled();
                $formRow->setTranslatorEnabled(false);
                $output = "<div class='table'>\n";
                foreach ($packages as $package) {
                    $output .= $view->formRow($package, 'append') . "\n\n";
                }
                $output .= "<span class='cell'></span>\n";
                $output .= $view->formSubmit($fieldset->get('Submit')) . "\n";
                $output .= "</div>\n";
                $formRow->setTranslatorEnabled($translatorEnabled);
            }
        }
        return $output;
    }
}
