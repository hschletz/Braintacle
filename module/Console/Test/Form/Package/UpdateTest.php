<?php

/**
 * Tests for Update form
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

namespace Console\Test\Form\Package;

use Laminas\Dom\Document\Query;

/**
 * Tests for Update form
 */
class UpdateTest extends \Console\Test\AbstractFormTest
{
    protected function getForm()
    {
        $packageManager = $this->createMock('Model\Package\PackageManager');
        $form = new \Console\Form\Package\Update();
        $form->setOption('packageManager', $packageManager);
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $fieldset = $this->_form->get('Deploy');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldset);
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldset->get('Pending'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldset->get('Running'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldset->get('Success'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldset->get('Error'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldset->get('Groups'));

        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->_form->get('Name')); // from parent class
    }
}
