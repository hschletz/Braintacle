<?php
/**
 * Form for defining and deleting userdefined fields
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 * Includes
 */
require_once ('Braintacle/Validate/NotInArray.php');
/**
 * Form for defining and deleting userdefined fields
 * @package Forms
 */
class Form_Definefields extends Zend_Form
{

    /**
     * Array of internal=>translated datatype pairs
     * @var array
     **/
    protected $_translatedTypes;

    /**
     * @ignore
     * Create elements and prepare datatype translations
     **/
    public function init()
    {
        $this->_translatedTypes = array(
            'text' => $this->getView()->translate('Text'),
            'clob' => $this->getView()->translate('Long text'),
            'integer' => $this->getView()->translate('Integer'),
            'float' => $this->getView()->translate('Float'),
            'date' => $this->getView()->translate('Date'),
        );

        $this->setMethod('post');

        // Create text elements for existing fields to rename them
        foreach (Model_UserDefinedInfo::getTypes() as $name => $type) {
            if ($name == 'tag') { // Static field, can not be edited
                continue;
            }
            // Since a field name can be an arbitrary string, the element name
            // gets base64 encoded to ensure validity.
            $element = new Zend_Form_Element_Text(base64_encode($name));
            // Since the field name is used as a column identifier in the
            // database, it is constrained to avoid serious trouble.
            $element->setValue($name)
                    ->setRequired(true)
                    ->addFilter('StringTrim')
                    ->addFilter('StringToLower')
                    ->addValidator('Regex', false, array('/^[a-z][a-z0-9_]*/'))
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator($this->_createBlacklistValidator($name));
            $this->addElement($element);
        }

        // Empty text field to create new field. Same constraints as above.
        // Name is prefixed with an underscore to avoid accidental clash with
        // base64 encoded name (very unlikely, but possible in theory)
        $newName = new Zend_Form_Element_Text('_newName');
        $newName->addFilter('StringTrim')
                ->addFilter('StringToLower')
                ->addValidator('Regex', false, array('/^[a-z][a-z0-9_]*/'))
                ->addValidator('StringLength', false, array(0, 255))
                ->addValidator($this->_createBlacklistValidator());
        $this->addElement($newName);

        // Datatype of new field
        $newType = new Zend_Form_Element_Select('newType');
        $newType->setDisableTranslator(true)
                ->setMultiOptions($this->_translatedTypes);
        $this->addElement($newType);

        $submit = new Zend_Form_Element_Submit('Submit');
        $submit->setLabel('Change');
        $this->addElement($submit);

        // Elements are rendered inside a table. Remove all decorators except
        // the form element itself and validation messages, not the other stuff
        // that does not fit into a table.
        foreach ($this->getElements() as $element) {
            $element->setDecorators(array('ViewHelper', 'Errors'));
        }
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
        foreach ($this->getElements() as $element) {
            $name = $element->getName();
            if ($name == '_newName') { // Stop here, remaining elements are rendered outside the loop
                break;
            }

            $name = base64_decode($name);
            $row = $view->htmlTag('td', $element->render());
            $row .= $view->htmlTag('td', $this->_translatedTypes[Model_UserDefinedInfo::getType($name)]);
            $row .= $view->htmlTag(
                'td',
                $view->htmlTag(
                    'a',
                    $view->translate('Delete'),
                    array('href' => $view->baseUrl() . '/preferences/deletefield/name/' . urlencode($name))
                )
            );
            $output .= $view->htmlTag('tr', $row);
        }
        // Row with elements for creating a new field
        $row = $view->htmlTag('td', $this->getElement('_newName')->render());
        $row .= $view->htmlTag('td', $this->getElement('newType')->render());
        $row .= $view->htmlTag('td', $view->translate('Add'));
        $output .= $view->htmlTag('tr', $row);

        // enclosing <table> tag
        $output = $view->htmlTag('table', $output);

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
     * Create a validator that forbids any existing column names except the given field
     * @param string $field Existing field name to allow (default: none)
     * @return Braintacle_Validate_NotInArray Validator object
     **/
    protected function _createBlacklistValidator($field=null)
    {
        $blacklist = Model_UserDefinedInfo::getFields();
        if ($field) {
            unset($blacklist[array_search($field, $blacklist)]);
        }
        $blacklist[] = 'hardware_id';
        $blacklist[] = 'tag';
        return new Braintacle_Validate_NotInArray($blacklist);
    }

    /**
     * Create and rename fields according to form content
     *
     * The form must be valid before calling this method.
     **/
    public function process()
    {
        foreach ($this->getElements() as $element) {
            $name = $element->getName();
            if ($name == '_newName') { // Stop here, remaining elements are processed outside the loop
                break;
            }
            $name = base64_decode($name);
            $value = $element->getValue();
            if ($value != $name) {
                Model_UserDefinedInfo::renameField($name, $value);
            }
        }
        $newName = $this->getElement('_newName')->getValue();
        if ($newName) {
            Model_UserDefinedInfo::addField($newName, $this->getElement('newType')->getValue());
        }
    }
}
