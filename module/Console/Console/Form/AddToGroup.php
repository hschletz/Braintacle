<?php
/**
 * Add search results to a group
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
 */

namespace Console\Form;

/**
 * Add search results to a group
 *
 * The "GroupManager" option is required for init() and process(). The factory
 * automatically injects a \Model\Group\GroupManager instance.
 */
class AddToGroup extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        $what = new \Zend\Form\Element\Radio('What');
        $what->setValueOptions(
            array(
                \Model_GroupMembership::TYPE_DYNAMIC => $this->_(
                    'Store search parameters. Group memberships will be updated automatically.'
                ),
                \Model_GroupMembership::TYPE_STATIC => $this->_(
                    'Add current search results. Group memberships will be set only this time.'
                ),
                \Model_GroupMembership::TYPE_EXCLUDED => $this->_(
                    'Exclude search results from a group.'
                )
            )
        );
        $what->setValue(\Model_GroupMembership::TYPE_DYNAMIC);
        $this->add($what);

        $where = new \Zend\Form\Element\Radio('Where');
        $where->setValueOptions(
            array(
                'new' => $this->_('Store in new group'),
                'existing' => $this->_('Store in existing group')
            )
        );
        $where->setValue('new')
              ->setAttribute('onchange', 'selectElements()');
        $this->add($where);

        $newGroup = new \Zend\Form\Element\Text('NewGroup');
        $newGroup->setLabel('Name');
        $this->add($newGroup);

        $description = new \Zend\Form\Element\Text('Description');
        $description->setLabel('Description');
        $this->add($description);

        $existingGroup = new \Library\Form\Element\SelectSimple('ExistingGroup');
        $existingGroup->setLabel('Group');
        $groups = array();
        foreach ($this->getOption('GroupManager')->getGroups(null, null, 'Name') as $group) {
            $groups[] = $group['Name'];
        }
        $existingGroup->setValueOptions($groups);
        $this->add($existingGroup);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('OK');
        $this->add($submit);

        $lengthValidator = new \Zend\Validator\Callback;
        $lengthValidator->setCallback(array($this, 'validateLength'))
                        ->setCallbackOptions(array(0, 255))
                        ->setTranslatorTextDomain('default')
                        ->setMessage('The input is more than 255 characters long');
        $requiredValidator = new \Zend\Validator\Callback;
        $requiredValidator->setCallback(array($this, 'validateLength'))
                          ->setCallbackOptions(array(1, 255))
                          ->setTranslatorTextDomain('default')
                          ->setMessage("Value is required and can't be empty");
        $existsValidator = new \Zend\Validator\Callback;
        $existsValidator->setCallback(array($this, 'validateGroupExists'))
                        ->setTranslatorTextDomain('default')
                        ->setMessage('The name already exists');
        $validatorChain = new \Zend\Validator\ValidatorChain;
        $validatorChain->attach($lengthValidator, true)
                       ->attach($requiredValidator, true)
                       ->attach($existsValidator, true);
        $inputFilter = new \Zend\InputFilter\InputFilter;
        $inputFilter->add(
            array(
                'name' => 'NewGroup',
                'continue_if_empty' => true,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array($validatorChain),
            )
        );
        $lengthValidator = new \Zend\Validator\Callback;
        $lengthValidator->setCallback(array($this, 'validateLength'))
                        ->setCallbackOptions(array(0, 255))
                        ->setTranslatorTextDomain('default')
                        ->setMessage('The input is more than 255 characters long');
        $inputFilter->add(
            array(
                'name' => 'Description',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null', 'options' => array('type' => 'string')),
                ),
                'validators' => array(
                    $lengthValidator
                ),
            )
        );
        $this->setInputFilter($inputFilter);
    }

    /**
     * Validator callback for new group name - check length if necessary
     *
     * @param string $value
     * @param array $context
     * @param integer $min
     * @param integer $max
     * @return bool
     * @internal
     */
    public function validateLength($value, $context, $min, $max)
    {
        if ($context['Where'] == 'new') {
            $length = \Zend\Stdlib\StringUtils::getWrapper('UTF-8')->strlen($value);
            $result = ($length >= $min and $length <= $max);
        } else {
            $result = true; // Field is ignored for existing groups
        }
        return $result;
    }

    /**
     * Validator callback for new group name - prevent duplicate name if necessary
     *
     * @param string $value
     * @param array $context
     * @return bool
     * @internal
     */
    public function validateGroupExists($value, $context)
    {
        if ($context['Where'] == 'new') {
            $result = !preg_grep(
                '/^' . preg_quote($value, '/') . '$/ui',
                $this->get('ExistingGroup')->getValueOptions()
            );
        } else {
            $result = true; // Field is ignored for existing groups
        }
        return $result;
    }

    /** {@inheritdoc} */
    public function render(\Zend\View\Renderer\PhpRenderer $view)
    {
        $view->headScript()->captureStart();
        ?>

        /**
         * Show/hide elements according to selected "Where" radio button
         */
        function selectElements()
        {
            var buttons = document.getElementsByName('Where');
            var newGroup;
            for (var i = 0; i < buttons.length; i++) {
                if (buttons[i].value == 'new') {
                    newGroup = buttons[i].checked;
                    break;
                }
            }
            if (newGroup) {
                display('NewGroup', true);
                display('Description', true);
                display('ExistingGroup', false);
            } else {
                display('NewGroup', false);
                display('Description', false);
                display('ExistingGroup', true);
            }
            var errors = document.getElementsByClassName('error');
            for (i = 0; i < errors.length; i++) {
                errors[i].style.display = newGroup ? 'block' : 'none';
            }
        }

        /**
         * Hide or show a form element
         *
         * name (string): element name
         * show (bool): true to show, false to hide
         */
        function display(name, show)
        {
            document.getElementsByName(name)[0].parentNode.style.display = show ? 'table-row' : 'none';
        }

        <?php
        $view->headScript()->captureEnd();
        $view->placeholder('BodyOnLoad')->append('selectElements()');

        return parent::render($view);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $output = "<div class='table'>\n";

        $output .= "<fieldset><legend><span>What to save</span></legend>\n";
        $output .= $view->formRow($fieldset->get('What'));
        $output .= "</fieldset>\n";

        $output .= "<fieldset><legend><span>Where to save</span></legend>\n";
        $output .= $view->formRow($fieldset->get('Where'));
        foreach (array('NewGroup', 'Description') as $name) {
            $element = $fieldset->get($name);
            $output .= $view->formRow($element, null, false);
            $output .= $view->formElementErrors($element, array('class' => 'error'));
        }
        $output .= $view->formRow($fieldset->get('ExistingGroup'));
        $output .= "</fieldset>\n";

        $output .= $view->formRow($fieldset->get('Submit'));
        $output .= "</div>\n";
        return $output;
    }

    /**
     * Add query/computers to group according to form data
     *
     * Form elements are not updated. The form instance is invalid after calling
     * process() and should no longer be used.
     *
     * @param mixed $filter Filter name(s)
     * @param mixed $search Search value(s)
     * @param mixed $operator operator(s)
     * @param mixed $invert Invert filter result(s)
     **/
    public function process($filter, $search, $operator, $invert)
    {
        $data = $this->getData();
        $groupManager = $this->getOption('GroupManager');
        if ($data['Where'] == 'new') {
            $groupManager->createGroup(
                $data['NewGroup'],
                $data['Description']
            );
            $group = $groupManager->getGroup($data['NewGroup']);
        } else {
            $group = $groupManager->getGroup($data['ExistingGroup']);
        }
        $group->setMembersFromQuery($data['What'], $filter, $search, $operator, $invert);
        return $group;
    }
}
