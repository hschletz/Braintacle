<?php
/**
 * Form for managing group memberships of a computer
 *
 * $Id$
 *
 * Copyright (C) 2011 Holger Schletz <holger.schletz@web.de>
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
 * @filesource
 */
/**
 * Form for managing group memberships of a computer
 *
 * This form does not create its elements automatically. This only happens when
 * calling the {@link addGroups()} method. Note that this must also be done
 * before validating or retrieving data.
 * @package Forms
 */
class Form_ManageGroupMemberships extends Zend_Form
{

    /**
     * Only sets method. Elements are added by addGroups().
     */
    public function init()
    {
        $this->setMethod('post');
    }

    /**
     * Create form elements for given computer
     *
     * @param Model_Computer $computer
     * @return integer Number of groups
     */
    public function addGroups(Model_Computer $computer)
    {
        $translate = Zend_Registry::get('Zend_Translate');

        // Values and labels for radio buttons
        $buttons = array(
            Model_GroupMembership::TYPE_DYNAMIC => $translate->_('automatic'),
            Model_GroupMembership::TYPE_STATIC => $translate->_('always'),
            Model_GroupMembership::TYPE_EXCLUDED => $translate->_('never'),
        );

        // Path to custom decorator
        $path = realpath(dirname(__FILE__) . '/Decorator');

        // Get a list of all groups
        $groups = Model_Group::createStatementStatic(
            array('Id', 'Name'),
            null,
            null,
            'Name'
        );

        // Get a list of computer's memberships to be used as defaults for radio buttons
        $memberships = $computer->getGroups(Model_GroupMembership::TYPE_ALL);
        $defaults = array();
        while ($membership = $memberships->fetchObject('Model_GroupMembership')) {
            $defaults[$membership->getGroupId()] = $membership->getMembership();
        }

        $numGroups = 0;

        // Create a set of radio buttons for each group
        while ($group = $groups->fetchObject('Model_Group')) {
            $groupId = $group->getId();
            $element = new Zend_Form_Element_Radio('group' . $groupId);
            $element->setDisableTranslator(true);
            $element->setLabel($group->getName());
            $element->setSeparator("&nbsp;&nbsp;\n"); // display inline
            $element->setMultiOptions($buttons);
            // Replace Label decorator with GroupLabel
            $element->addPrefixPath('Form_Decorator', $path, 'decorator');
            $element->removeDecorator('Label');
            $element->addDecorator('GroupLabel');

            // Set default state for radio button. It tells how this particular
            // computer/group combination is to be treated for membership
            // calculation. For dynamic membership, the computer does not
            // actually need to meet membership criteria, i.e. it does not need
            // to be a member at this time. In that case, the radio button is
            // set to 'automatic'.
            if (isset($defaults[$groupId])) {
                $value = $defaults[$groupId];
            } else {
                $value = Model_GroupMembership::TYPE_DYNAMIC;
            }
            $element->setValue($value);

            $this->addElement($element);
            $numGroups++;
        }

        // If no groups are defined, keep the form empty, i.e. don't create any
        // elements at all.
        if ($numGroups) {
            $id = new Zend_Form_Element_Hidden('id');
            $id->setDisableTranslator(true);
            $id->setIgnore(true);
            $id->setValue($computer->getId());
            $this->addElement($id);

            $submit = new Zend_Form_Element_Submit('submit');
            $submit->setRequired(false)
                ->setIgnore(true)
                ->setLabel('OK');
            $this->addElement($submit);
        }

        return $numGroups;
    }

    /**
     * Return plain group IDs instead of raw element names
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = array();
        foreach (parent::getValues($suppressArrayNotation) as $name => $value) {
            $name = self::extractGroupId($name);
            $values[$name] = $value;
        }
        return $values;
    }

    /**
     * Extract numeric group ID from element name
     *
     * Element names can't consist of only digits. For this reason, the names
     * of the radio buttons are prefixed with 'group'. This method removes the
     * prefix and returns the plain numeric ID.
     * @param string $name Element name, like 'group42'
     * @return integer The numeric part of $name
     */
    static function extractGroupId($name)
    {
        if (!preg_match('/^group([0-9]+)$/', $name, $matches)) {
            throw new UnexpectedValueException(
                'Invalid naming schema for element ' . $name
            );
        }
        return (int) $matches[1];
    }

}
