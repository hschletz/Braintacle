<?php
/**
 * Form for inventory data upload
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
 * @filesource
 */
/**
 * Form for inventory data upload
 * @package Forms
 */
class Form_Import extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('enctype', 'multipart/form-data');

        // Upload file
        $file = new Zend_Form_Element_File('File');
        $file->addValidator('Count', false, 1)
             ->setRequired(true)
             ->setLabel('File (*.ocs, *.xml)');
        $this->addElement($file);

        // Submit button
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setRequired(false)
               ->setIgnore(true)
               ->setLabel('Import');
        $this->addElement($submit);
    }

}
