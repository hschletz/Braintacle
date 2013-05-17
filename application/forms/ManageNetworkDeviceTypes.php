<?php
/**
 * Form for defining and deleting network device types
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
 * Form for defining and deleting network device types
 * @package Forms
 */
class Form_ManageNetworkDeviceTypes extends Zend_Form
{

    /**
     * Array of all types defined in the database
     * @var array[Model_NetworkDeviceType]
     **/
    protected $_definedTypes = array();

    /**
     * Array of all defined types that can be deleted
     * @var array[Model_NetworkDeviceType]
     **/
    protected $_deletableTypes = array();

    /**
     * @ignore
     **/
    public function init()
    {
        $this->setMethod('post');

        $statement = Model_NetworkDeviceType::createStatementStatic();
        while ($type = $statement->fetchObject('Model_NetworkDeviceType')) {
            $id = $type->getId();
            if (!$id) {
                continue;
            }
            $this->_definedTypes[$id] = $type;
            if ($type->getCount() == 0) {
                $this->_deletableTypes[$id] = $type;
            }
        }

        // Create text elements for existing types to rename them
        foreach ($this->_definedTypes as $type) {
            $description = $type->getDescription();
            $element = new Zend_Form_Element_Text('type_' . $type->getId());
            $element->setValue($description)
                    ->setRequired(true)
                    ->addFilter('StringTrim')
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator($this->_createBlacklistValidator($description));
            $this->addElement($element);
        }

        // Empty text field to create new field.
        $new = new Zend_Form_Element_Text('new');
        $new->addFilter('StringTrim')
            ->addValidator('StringLength', false, array(0, 255))
            ->addValidator($this->_createBlacklistValidator());
        $this->addElement($new);

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel('Change');
        $this->addElement($submit);

        // Elements are rendered inside a table. Remove all decorators except
        // the form element itself and validation messages, not the other stuff
        // that does not fit into a table.
        $this->setElementDecorators(array('ViewHelper', 'Errors'));
    }

    /**
     * @ignore
     * Render form as table
     */
    public function render(Zend_View_Interface $view=null)
    {
        if (!$view) {
            $view = $this->getView();
        }
        $output = '';

        // Create table rows for each existing field
        foreach ($this->_definedTypes as $type) {
            $id = $type->getId();
            $row = $view->htmlTag('td', $this->getElement("type_$id")->render());
            if (isset($this->_deletableTypes[$id])) {
                $row .= $view->htmlTag(
                    'td',
                    $view->htmlTag(
                        'a',
                        $view->translate('Delete'),
                        array(
                            'href' => $view->standardUrl(
                                array(
                                    'controller' => 'preferences',
                                    'action' => 'deletedevicetype',
                                    'id' => $id,
                                )
                            )
                        )
                    )
                );
            } else {
                $row .= '<td></td>';
            }
            $output .= $view->htmlTag('tr', $row);

        }
        // Row with element for creating a new field
        $row = $view->htmlTag(
            'td',
            $view->translate('Add') . '<br>' . $this->getElement('new')->render()
        ) . '<td></td>';
        $output .= $view->htmlTag('tr', $row);

        // enclosing <table> tag
        $output = $view->htmlTag('table', $output, array('class' => 'Form_ManageNetworkDeviceTypes'));

        // submit button
        $output .= $view->htmlTag(
            'p',
            $this->getElement('Submit')->render(),
            array('class' => 'textcenter')
        );

        // enclosing <form>
        $output = $this->renderForm($output);

        return $output;
    }

    /**
     * Create a validator that forbids any existing description except the given one
     * @param string $description Existing description to allow (default: none)
     * @return Braintacle_Validate_NotInArray Validator object
     **/
    protected function _createBlacklistValidator($description=null)
    {
        $blacklist = array();
        foreach ($this->_definedTypes as $type) {
            $blacklist[] = $type->getDescription();
        }
        if ($description) {
            unset($blacklist[array_search($description, $blacklist)]);
        }
        return new Braintacle_Validate_NotInArray(
            $blacklist,
            Braintacle_Validate_NotInArray::CASE_SENSITIVE
        );
    }

    /**
     * Add and rename types according to form data
     **/
    public function process()
    {
        $description = $this->getValue('new');
        if ($description) {
            Model_NetworkDeviceType::add($description);
        }
        foreach ($this->_definedTypes as $id => $type) {
            $type->rename($this->getValue("type_$id"));
        }
    }
}
