<?php

/**
 * Form for inventory data upload
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
 * Form for inventory data upload
 *
 * The uploaded file (required) is accessed through the "File" element.
 */
class Import extends Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $file = new \Laminas\Form\Element\File('File');
        $file->setLabel('File (*.ocs, *.xml)');
        $this->add($file);

        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Import');
        $this->add($submit);

        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $inputFilter->add(array('name' => 'File')); // Sufficient to force uploaded file
        $this->setInputFilter($inputFilter);
    }
}
