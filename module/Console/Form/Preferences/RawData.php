<?php

/**
 * Form for display/setting of 'raw data' preferences
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

namespace Console\Form\Preferences;

/**
 * Form for display/setting of 'raw data' preferences
 */
class RawData extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();
        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();

        $saveRawData = new \Laminas\Form\Element\Checkbox('saveRawData');
        $saveRawData->setLabel('Save incoming raw inventory data');
        $preferences->add($saveRawData);

        $saveDir = new \Laminas\Form\Element\Text('saveDir');
        $saveDir->setLabel('Target directory');
        $preferences->add($saveDir);

        $saveFormat = new \Laminas\Form\Element\Select('saveFormat');
        $saveFormat->setLabel('File format')
                   ->setValueOptions(
                       array(
                        'XML' => $this->_('uncompressed XML'),
                        'OCS' => $this->_('zlib compressed XML')
                       )
                   );
        $preferences->add($saveFormat);
        $inputFilter->add(
            array(
                'name' => 'saveDir',
                'validators' => array(
                    array('name' => 'Library\Validator\DirectoryWritable')
                )
            )
        );

        $saveOverwrite = new \Laminas\Form\Element\Checkbox('saveOverwrite');
        $saveOverwrite->setLabel('Overwrite existing files');
        $preferences->add($saveOverwrite);

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $this->getInputFilter()->get('Preferences')->get('saveDir')->setRequired(
            $data['Preferences']['saveRawData']
        );
        return parent::setData($data);
    }
}
