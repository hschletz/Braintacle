<?php

/**
 * Tests for Import form
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

namespace Console\Test\Form;

use Laminas\InputFilter\FileInput;

/**
 * Tests for Import form
 */
class ImportTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $this->assertInstanceOf('Laminas\Form\Element\File', $this->_form->get('File'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
        $this->assertInstanceOf(FileInput::class, $this->_form->getInputFilter()->get('File'));
    }

    public function testInputFilterFileMissing()
    {
        $data = array(
            'File' => array(
                'tmp_name' => '',
                'name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ),
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['File'];
        $this->assertArrayHasKey(\Laminas\Validator\File\UploadFile::NO_FILE, $messages);
    }

    public function testInputFilterFileValid()
    {
        // UploadFile validator does not work with vfsStream wrapper.
        // Use real temporary file.
        $tmpFile = tmpfile();
        $file = stream_get_meta_data($tmpFile)['uri'];
        $data = array(
            'File' => array(
                'tmp_name' => $file,
                'name' => 'uploaded_file',
                'error' => UPLOAD_ERR_OK,
            ),
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        // UploadFile validator calls is_uploaded_file() which cannot easily be
        // mocked. Validation will fail. Test for correct error count and type.
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages['File']);
        $this->assertArrayHasKey(\Laminas\Validator\File\UploadFile::ATTACK, $messages['File']);
    }

    public function testEnctypeAttribute()
    {
        $html = $this->_form->render($this->createView());
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(
            1,
            \Laminas\Dom\Document\Query::execute('//form[@enctype="multipart/form-data"]', $document)
        );
    }
}
