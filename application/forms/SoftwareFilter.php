<?php
/**
 * Filters for software overview.
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
 * Filters for software overview.
 * @package Forms
 */
class Form_SoftwareFilter extends Zend_Form
{

    /**
     * Build form elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        $this->setMethod('get');

        $filter = new Zend_Form_Element_Select('filter');
        $filter->setLabel('Filter')
               ->setDisableTranslator(true) // Translate manually to make xgettext find the strings
               ->setMultiOptions(
                   array(
                       'accepted' => $translate->_('selected for display'),
                       'ignored' => $translate->_('ignored for display'),
                       'new' => $translate->_('new or not categorized'),
                       'all' => $translate->_('all'),
                   )
               )
               ->setAttrib('onchange', 'changeFilter();');
        $this->addElement($filter);

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Apply')
               ->setIgnore(true);
        $this->addElement($submit);
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

        /*
           Event handler for Filter combobox.
           Triggers a form submit.
        */
        function changeFilter()
        {
            document.getElementById("submit").click();
        }

        <?php
        $view->headScript()->captureEnd();

        return parent::render($view);
    }

    /**
     * Set the value for the 'Filter' element
     * @param string One of accepted|ignored|new|all. Other values trigger an exception.
     */
    public function setFilter($filter)
    {
        $element = $this->getElement('filter');
        if (!array_key_exists($filter, $element->getMultiOptions())) {
            throw new UnexpectedValueException('Invalid filter value: ' . $filter);
        }
        $element->setValue($filter);
    }

}
