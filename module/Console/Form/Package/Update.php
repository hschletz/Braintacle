<?php

/**
 * Form for updating an existing package
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

namespace Console\Form\Package;

/**
 * Form for updating an existing package
 *
 * In addition to the fields provided by the Build form, a fieldset "Deploy"
 * provides checkboxes:
 *
 * - Pending
 * - Running
 * - Success
 * - Error
 * - Groups
 */
class Update extends Build
{
    /** {@inheritdoc} */
    public function init()
    {
        $fieldset = new \Laminas\Form\Fieldset('Deploy');
        $fieldset->setLabel('Deploy to clients which have existing package assigned');

        $deployPending = new \Laminas\Form\Element\Checkbox('Pending');
        $deployPending->setLabel('Pending');
        $fieldset->add($deployPending);

        $deployRunning = new \Laminas\Form\Element\Checkbox('Running');
        $deployRunning->setLabel('Running');
        $fieldset->add($deployRunning);

        $deploySuccess = new \Laminas\Form\Element\Checkbox('Success');
        $deploySuccess->setLabel('Success');
        $fieldset->add($deploySuccess);

        $deployError = new \Laminas\Form\Element\Checkbox('Error');
        $deployError->setLabel('Error');
        $fieldset->add($deployError);

        $deployGroups = new \Laminas\Form\Element\Checkbox('Groups');
        $deployGroups->setLabel('Groups');
        $fieldset->add($deployGroups);

        $this->add($fieldset);
        parent::init();
    }
}
