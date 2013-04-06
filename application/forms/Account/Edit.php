<?php
/**
 * Form to edit existing Braintacle user accounts
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * @package Forms
 */
/**
 * Form to edit existing Braintacle user accounts
 * @package Forms
 */
class Form_Account_Edit extends Form_Account
{

    /**
     * Create elements
     */
    public function init()
    {
        parent::init();

        // Password can remain empty - in that case, it is left untouched.
        $this->getElement('Password')->setRequired(false);
        $this->getElement('PasswordRepeat')->setRequired(false);

        $this->getElement('submit')->setLabel('Change');

        // Required to keep track of original ID if this gets changed.
        $originalId = new Zend_Form_Element_Hidden('OriginalId');
        $this->addElement($originalId);
    }

    /**
     * Populate form with existing account data
     * @param $id Login name of existing acount. Must be valid.
     */
    public function setId($id)
    {
        $originalId = $this->getElement('OriginalId');
        if ($originalId->getValue()) {
            return; // Form already populated. Don't overwrite.
        }

        $originalId->setValue($id);
        $account = new Model_Account($id);
        foreach ($account as $property => $value) {
            $this->getElement($property)->setValue($value);
        }
    }

    /**
     * Validate the form
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // If account gets renamed, make sure it does not clash with existing name
        if ($data['Id'] != $data['OriginalId']) {
            $this->getElement('Id')->addValidator(
                'Db_NoRecordExists', false, array(
                    'table' => 'operators',
                    'field' => 'id'
                )
            );
        }
        // Validation logic will not work with empty password fields.
        // Make them required if at least 1 field is non-empty.
        if ($data['Password'] or $data['PasswordRepeat']) {
            $this->getElement('Password')->setRequired(true);
            $this->getElement('PasswordRepeat')->setRequired(true);
        }
        return parent::isValid($data);
    }
}
