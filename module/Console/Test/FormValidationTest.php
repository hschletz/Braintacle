<?php

/**
 * Form validation test case
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

namespace Console\Test;

class FormValidationTest extends \PHPUnit\Framework\TestCase
{
    protected $_postBackup;
    protected $_filesBackup;
    protected $_serverBackup;

    public function setUp(): void
    {
        parent::setUp();
        $this->_postBackup = $_POST;
        $this->_filesBackup = $_FILES;
        $this->_serverBackup = $_SERVER;
    }

    public function tearDown(): void
    {
        $_POST = $this->_postBackup;
        $_FILES = $this->_filesBackup;
        $_SERVER = $this->_serverBackup;
        parent::tearDown();
    }

    public function testIsValidOk()
    {
        $_SERVER = array();

        $form = new \Console\Form\Form();
        $form->setData(array());

        $this->assertTrue($form->isValid());
    }

    public function testIsValidPostMaxSizeExceeded()
    {
        $_POST = array();
        $_FILES = array();
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $form = new \Console\Form\Form();
        $form->setData(array());

        $this->assertFalse($form->isValid());
    }
}
