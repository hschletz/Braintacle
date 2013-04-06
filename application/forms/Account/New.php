<?php
/**
 * Form for adding Braintacle user accounts
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
 * Form adding Braintacle user accounts
 * @package Forms
 */
class Form_Account_New extends Form_Account
{

    /**
     * Create elements
     */
    public function init()
    {
        parent::init();

        // New accounts must not have an existing ID.
        $this->getElement('Id')->addValidator(
            'Db_NoRecordExists', false, array(
                'table' => 'operators',
                'field' => 'id'
            )
        );

        $this->getElement('submit')->setLabel('Add');
    }

}
