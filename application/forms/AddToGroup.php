<?php
/**
 * Form for adding search results to a group
 *
 * $Id$
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
 * Form for adding search results to a group
 * @package Forms
 */
class Form_AddToGroup extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->setMethod('post');
        $this->setDecorators(
            array(
                'FormElements',
                array('HtmlTag', array('tag' => 'table', 'class' => 'Form_AddToGroup')),
                'Form'
            )
        );

        $radioDecorator = array(
            'ViewHelper',
            'Errors',
            array(array('td' => 'HtmlTag'), 'options' => array('tag' => 'td', 'colspan' => 2)),
            array(array('tr' => 'HtmlTag'), 'options' => array('tag' => 'tr')),
        );
        $labelDecorator = array(
            'ViewHelper',
            'Errors',
            array(array('td' => 'HtmlTag'), 'options' => array('tag' => 'td', 'class' => 'formElement')),
            array('Label', array('tag' => 'td')),
            array(array('tr' => 'HtmlTag'), 'options' => array('tag' => 'tr')),
        );
        $buttonDecorator = array(
            'ViewHelper',
            'Errors',
            array(array('td' => 'HtmlTag'), 'options' => array('tag' => 'td')),
            array(array('label' => 'HtmlTag'), array('tag' => 'td', 'placement' => 'prepend')),
            array(array('tr' => 'HtmlTag'), 'options' => array('tag' => 'tr')),
        );

        $what = new Zend_Form_Element_Radio('What');
        $what->setDisableTranslator(true)
             ->setMultiOptions(
                 array(
                    'filter' => $translate->_(
                        'Store search parameters. Group memberships will be updated automatically.'
                    ),
                    'result' => $translate->_(
                        'Add current search results. Group memberships will be set only this time.'
                    )
                )
             )
             ->setValue('filter')
             ->setDecorators($radioDecorator);
        $this->addElement($what);

        $where = new Zend_Form_Element_Radio('Where');
        $where->setDisableTranslator(true)
              ->setMultiOptions(
                  array(
                    'new' => $translate->_('Store in new group'),
                    'existing' => $translate->_('Store in existing group')
                  )
              )
              ->setValue('new')
              ->setAttrib('onchange', 'whereChanged();')
              ->setDecorators($radioDecorator);
        $this->addElement($where);

        $newGroup = new Zend_Form_Element_Text('newGroup');
        $newGroup->setLabel('Name')
                 ->addFilter('StringTrim')
                 ->setDecorators($labelDecorator);
        // Validators are set in isValid() because thy depend on the 'Where' value.
        $this->addElement($newGroup);

        $description = new Zend_Form_Element_Text('Description');
        $description->setLabel('Description')
                    ->addValidator('StringLength', false, array(0, 255))
                    ->setDecorators($labelDecorator);
        $this->addElement($description);

        $existingGroup = new Zend_Form_Element_Select('existingGroup');
        $existingGroup->setLabel($translate->_('Group'))
                      ->setDisableTranslator(true)
                      ->setDecorators($labelDecorator);
        $statement = Model_Group::createStatementStatic(
            array('Id', 'Name'),
            null,
            null,
            'Name'
        );
        while ($group = $statement->fetchObject('Model_Group')) {
            $existingGroup->addMultiOption($group->getId(), $group->getName());
        }
        $this->addElement($existingGroup);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('OK')
        ->setDecorators($buttonDecorator);
        $this->addElement($submit);
    }

    /**
     * Validate the form
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        if ($data['Where'] == 'new') {
            // Add extra validators which are only suitable for new groups
            $this->newGroup
                ->addValidator('StringLength', false, array(1, 255))
                ->addValidator(
                    'Db_NoRecordExists', false, array(
                        'table' => 'hardware',
                        'field' => 'name',
                        'exclude' => "deviceid = '_SYSTEMGROUP_'"
                    )
                )
                ->setRequired(true);
        }
        return parent::isValid($data);
    }

    /**
     * Get group object referenced by form data. A new group is created if requested.
     * @return Model_Group
     **/
    public function getGroup()
    {
        if ($this->getValue('Where') == 'new') {
            $group = Model_Group::create(
                $this->getValue('newGroup'),
                $this->getValue('Description')
            );
        } else {
            $group = Model_Group::fetchById($this->getValue('existingGroup'));
            if (!$group) {
                throw new RuntimeException('Invalid group ID: ' . $this->getValue('existingGroup'));
            }
        }
        return $group;
    }

    /**
     * Render form
     * @param Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view=null)
    {
        $view = $this->getView();

        $view->headScript()->captureStart();
        ?>

        // Show/hide fields according to selected "Where" radio button.
        // Hide error messages.
        function whereChanged()
        {
            selectElements();
            // Hide error messages
            var errors = document.getElementsByClassName('errors');
            for (var i = 0; i < errors.length; i++) {
                errors[i].style.display = 'none';
            }
        }

        // Show/hide fields according to selected "Where" radio button.
        function selectElements()
        {
            if (document.getElementById('Where-new').checked) {
                display('newGroup', true);
                display('Description', true);
                display('existingGroup', false);
            } else {
                display('newGroup', false);
                display('Description', false);
                display('existingGroup', true);
            }
        }

        /**
         * Hide or display a form element.
         * id (string): element name
         * display (bool): true to display, false to hide
         */
        function display(id, display)
        {
            if (display) {
                display = "table-row";
            } else {
                display = "none";
            }
            document.getElementById(id).parentNode.parentNode.style.display = display;
        }

        /**
         * Called by body.onload().
         */
        function init()
        {
            selectElements();
        }

        <?php
        $view->headScript()->captureEnd();

        return parent::render($view);
    }
}
