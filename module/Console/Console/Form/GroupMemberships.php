<?php
/**
 * Form for managing a computer's group memberships
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
 * Form for managing a computer's group memberships
 *
 * Available groups are set via setPackages() or setData(). Each group has a
 * radio button set (with the same name as the package) in the "Groups" fieldset
 * with 3 buttons:
 *
 * - value \Model_GroupMembership::TYPE_DYNAMIC ("automatic")
 * - value \Model_GroupMembership::TYPE_STATIC ("always")
 * - value \Model_GroupMembership::TYPE_EXCLUDED ("never")
 */
class GroupMemberships extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setText('OK');
        $this->add($submit);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        if (isset($data['Groups'])) {
            $groups = array_keys($data['Groups']);
        } else {
            $groups = array();
        }
        $this->setGroups($groups);
        return parent::setData($data);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Zend\View\Renderer\PhpRenderer $view, \Zend\Form\Fieldset $fieldset)
    {
        $output = '';
        if ($fieldset->has('Groups')) {
            $groups = $fieldset->get('Groups');
            if ($groups->count()) {
                $output = "<div>\n";
                foreach ($groups as $element) {
                    if ($element instanceof \Zend\Form\Element\Radio) {
                        $label = $view->htmlTag(
                            'a',
                            $view->escapeHtml($element->getLabel()),
                            array(
                                'href' => $view->consoleUrl(
                                    'group',
                                    'general',
                                    array('name' => $element->getLabel())
                                )
                            )
                        );
                        $output .= $view->htmlTag(
                            'fieldset',
                            "<legend>$label</legend>\n" . $view->formRadio($element)
                        );
                    }
                }
                $output .= $view->formRow($fieldset->get('Submit'));
                $output .= "\n</div>\n";
            }
        }
        return $output;
    }

    /**
     * Set available groups
     *
     * The "Groups" fieldset is (re)created with radio buttons for each group.
     *
     * @param string[] $groups Group names
     */
    public function setGroups(array $groups)
    {
        if ($this->has('Groups')) {
            $this->remove('Groups');
        }
        $fieldset= new \Zend\Form\Fieldset('Groups');
        $this->add($fieldset);

        $buttons = array(
            \Model_GroupMembership::TYPE_DYNAMIC => $this->_('automatic'),
            \Model_GroupMembership::TYPE_STATIC => $this->_('always'),
            \Model_GroupMembership::TYPE_EXCLUDED => $this->_('never'),
        );
        foreach ($groups as $group) {
            $element = new \Zend\Form\Element\Radio($group);
            $element->setLabel($group);
            $element->setValueOptions($buttons);
            $element->setValue(\Model_GroupMembership::TYPE_DYNAMIC);
            $fieldset->add($element);
        }
    }
}
