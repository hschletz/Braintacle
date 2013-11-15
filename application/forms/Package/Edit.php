<?php
/**
 * Form for creating a package based on an exiting package
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
 * Form for creating a package based on an exiting package
 *
 * In addition to the fields provided by {@link Form_Package}, the following
 * boolean fields are provided:
 *
 * - DeployNonnotified
 * - DeploySuccess
 * - DeployNotified
 * - DeployError
 * @package Forms
 */
class Form_Package_Edit extends Form_Package
{

    /**
     * Add additional elements for deploying the package
     */
    public function init()
    {
        $config = \Library\Application::getService('Model\Config');

        $deployNonnotified = new Zend_Form_Element_Checkbox('DeployNonnotified');
        $deployNonnotified->setLabel('Not notified')
            ->setChecked($config->defaultDeployNonnotified);
        $this->addElement($deployNonnotified);

        $deploySuccess = new Zend_Form_Element_Checkbox('DeploySuccess');
        $deploySuccess->setLabel('Success')
            ->setChecked($config->defaultDeploySuccess);
        $this->addElement($deploySuccess);

        $deployNotified = new Zend_Form_Element_Checkbox('DeployNotified');
        $deployNotified->setLabel('Running')
            ->setChecked($config->defaultDeployNotified);
        $this->addElement($deployNotified);

        $deployError = new Zend_Form_Element_Checkbox('DeployError');
        $deployError->setLabel('Error')
            ->setChecked($config->defaultDeployError);
        $this->addElement($deployError);

        $deployGroups = new Zend_Form_Element_Checkbox('DeployGroups');
        $deployGroups->setLabel('Groups')
            ->setChecked($config->defaultDeployGroups);
        $this->addElement($deployGroups);

        $this->addDisplayGroup(
            array(
                'DeployNonnotified',
                'DeploySuccess',
                'DeployNotified',
                'DeployError',
                'DeployGroups',
            ),
            'Deploy'
        );
        $this->getDisplayGroup('Deploy')->setLegend(
            'Deploy to computers which have existing package assigned'
        );

        parent::init();
    }

    /**
     * Populate form with values from a package object
     * @param Model_Package Package with values to put into the form
     */
    public function setValuesFromPackage($package)
    {
        foreach ($package as $property => $value) {
            $element = $this->getElement($property);
            if ($element) {
                $element->setValue($value);
            }
        }
    }
}
