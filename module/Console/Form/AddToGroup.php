<?php

/**
 * Add search results to a group
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
        parent::init();

        $what = new \Laminas\Form\Element\Radio('What');
        $what->setValueOptions(
            array(
                \Model\Client\Client::MEMBERSHIP_AUTOMATIC => $this->_(
                    'Store search parameters. Group memberships will be updated automatically.'
                ),
                \Model\Client\Client::MEMBERSHIP_ALWAYS => $this->_(
                    'Add current search results. Group memberships will be set only this time.'
                ),
                \Model\Client\Client::MEMBERSHIP_NEVER => $this->_(
                    'Exclude search results from a group.'
                )
            )
        );
        $what->setValue(\Model\Client\Client::MEMBERSHIP_AUTOMATIC);
        $what->setLabelAttributes(array('class' => 'what'));
        $this->add($what);

        $where = new \Laminas\Form\Element\Radio('Where');
        $where->setValueOptions(
            array(
                'new' => $this->_('Store in new group'),
                'existing' => $this->_('Store in existing group')
            )
        );
        $where->setValue('new');
        $where->setLabelAttributes(array('class' => 'where'));
        $this->add($where);

        $newGroup = new \Laminas\Form\Element\Text('NewGroup');
        $newGroup->setLabel('Name');
        $this->add($newGroup);

        $description = new \Laminas\Form\Element\Text('Description');
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

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add(
            array(
                'name' => 'NewGroup',
                'continue_if_empty' => true,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateLength'),
                            'callbackOptions' => array(0, 255),
                            'message' => $this->_('The input is more than 255 characters long'),
                        ),
                        'break_chain_on_failure' => true,
                    ),
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateLength'),
                            'callbackOptions' => array(1, 255),
                            'message' => "Value is required and can't be empty", // default notEmpty message
                        ),
                        'break_chain_on_failure' => true,
                    ),
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateGroupExists'),
                            'message' => $this->_('The name already exists'),
                        ),
                    ),
                ),
            )
        );
        $inputFilter->add(
            array(
                'name' => 'Description',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                    array('name' => 'Null', 'options' => array('type' => 'string')),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateLength'),
                            'callbackOptions' => array(0, 255),
                            'message' => $this->_('The input is more than 255 characters long'),
                        ),
                    )
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
            $length = \Laminas\Stdlib\StringUtils::getWrapper('UTF-8')->strlen($value);
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

    /**
     * Add query/clients to group according to form data
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
