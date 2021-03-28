<?php

/**
 * Form for accepting/ignoring software
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Form;

/**
 * Form for accepting/ignoring software
 *
 * Available software is set via setSoftware() or setData(). Each software has a
 * checkbox in the "Software" fieldset. The checkbox name is Base64 encoded and
 * prefixed with an underscore because incorrectly encoded characters (those to
 * be fixed by the FixEncodingErrors filter) would get misinterpreted by
 * browsers, but the original (bad) characters need to be preserved for further
 * processing. The checkbox labels have their encoding fixed. The prefix is
 * necessary because the software name may be empty and an empty checkbox name
 * is not allowed.
 *
 * Unlike standard Laminas checkboxes, no hidden input elements are generated.
 * This allows posting only selected entries instead of the full list (which can
 * grow large). Form handlers can simply iterate over the keys of the 'Software'
 * array, ignoring the values. The keys need to be Base64 decoded.
 *
 * The following options are supported:
 * - fixEncodingErrors (required, set by factory): an instance of
 *   \Library\Filter\FixEncodingErrors
 */
class Software extends \Console\Form\Form
{
    /**
     * Software list passed to setSoftware()
     * @var array[]
     */
    protected $_software;

    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $accept = new \Library\Form\Element\Submit('Accept');
        $accept->setLabel('Accept selected');
        $this->add($accept);

        $ignore = new \Library\Form\Element\Submit('Ignore');
        $ignore->setLabel('Ignore selected');
        $this->add($ignore);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        if (isset($data['Software'])) {
            $software = array_keys($data['Software']);
        } else {
            $software = array();
        }
        $this->createSoftwareFieldset($software, true);
        return parent::setData($data);
    }

    /**
     * Set available software
     *
     * The "Software" fieldset is (re)created with checkboxes for each software.
     *
     * @param array[] $software Data structure compatible with \Model\SoftwareManager::getSoftware() output
     */
    public function setSoftware(array $software)
    {
        $this->_software = $software;
        $this->createSoftwareFieldset(array_column($software, 'name'), false);
    }

    /**
     * Create "Software" fieldset with checkbox elements
     *
     * This is typically not invoked directly. setData() and setSoftware() will
     * create the fieldset.
     *
     * @param string[] $names Software names (unfiltered)
     * @param bool $namesEncoded Are names already Base64 encoded?
     */
    public function createSoftwareFieldset($names, $namesEncoded)
    {
        $filter = $this->getOption('fixEncodingErrors');
        if (!$filter instanceof \Library\Filter\FixEncodingErrors) {
            throw new \LogicException('FixEncodingErrors filter not set');
        }

        if ($this->has('Software')) {
            $this->remove('Software');
        }
        $fieldset = new \Laminas\Form\Fieldset('Software');
        $this->add($fieldset);

        foreach ($names as $name) {
            if ($namesEncoded) {
                $elementName = $name;
                $label = base64_decode(ltrim($name, '_'));
            } else {
                $elementName = '_' . base64_encode($name);
                $label = $name;
            }
            $element = new \Laminas\Form\Element\Checkbox($elementName);
            $element->setUseHiddenElement(false);
            $element->setLabel($filter($label));
            $fieldset->add($element);
        }
    }
}
