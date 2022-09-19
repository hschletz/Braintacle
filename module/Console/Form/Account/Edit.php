<?php

/**
 * Edit existing Braintacle user accounts
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
 */

namespace Console\Form\Account;

/**
 * Edit existing Braintacle user accounts
 */
class Edit extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        // Required to keep track of original ID if this gets changed.
        // This must be added before getInputFilter() is called.
        $originalId = new \Laminas\Form\Element\Hidden('OriginalId');
        $this->add($originalId);

        parent::init();
        $inputFilter = $this->getInputFilter();

        $inputFilter->add(
            array(
                'name' => 'Id',
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array('callback' => array($this, 'validateId')),
                    ),
                ),
            )
        );

        // Password can remain empty - in that case, it is left untouched.
        $inputFilter->get('Password')->setAllowEmpty(true);

        $this->get('Submit')->setLabel('Change');
    }

    /**
     * ID validation callback
     * @internal
     */
    public function validateId($value, $context)
    {
        // User name must not exist except when unchanged.
        // Names are treated case insensitive.
        if (strcasecmp($value, $context['OriginalId']) == 0) {
            return true;
        } else {
            // Search list of existing IDs for $value.
            // Succeed only if no match is found.
            return !preg_grep(
                '/^' . preg_quote($value, '/') . '$/ui',
                $this->getOption('operatorManager')->getAllIds()
            );
        }
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        if (empty($data['OriginalId'])) {
            throw new \LogicException('OriginalId not set or empty');
        }
        return parent::setData($data);
    }
}
