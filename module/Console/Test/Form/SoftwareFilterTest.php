<?php

/**
 * Tests for SoftwareFilter
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

use Console\Form\SoftwareFilter;

/**
 * Tests for SoftwareFilter
 */
class SoftwareFilterTest extends \Console\Test\AbstractFormTest
{
    protected function getForm(): SoftwareFilter
    {
        $form = new \Console\Form\SoftwareFilter();
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertEquals('GET', $this->_form->getAttribute('method'));
        $filter = $this->_form->get('filter');
        $this->assertInstanceOf('Laminas\Form\Element\Select', $filter);
        $this->assertEquals('this.form.submit();', $filter->getAttribute('onchange'));
        $options = $filter->getValueOptions();
        $this->assertArrayHasKey('accepted', $options);
        $this->assertArrayHasKey('ignored', $options);
        $this->assertArrayHasKey('new', $options);
        $this->assertArrayHasKey('all', $options);
        $this->assertCount(4, $options);
    }

    public function testSetFilterAccepted()
    {
        $form = $this->getForm();
        $form->setFilter('accepted');
        $this->assertEquals('accepted', $form->get('filter')->getValue());
    }

    public function testSetFilterIgnored()
    {
        $form = $this->getForm();
        $form->setFilter('ignored');
        $this->assertEquals('ignored', $form->get('filter')->getValue());
    }

    public function testSetFilterNew()
    {
        $form = $this->getForm();
        $form->setFilter('new');
        $this->assertEquals('new', $form->get('filter')->getValue());
    }

    public function testSetFilterAll()
    {
        $form = $this->getForm();
        $form->setFilter('all');
        $this->assertEquals('all', $form->get('filter')->getValue());
    }

    public function testSetFilterInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid filter value: invalid');

        $form = $this->getForm();
        $form->setFilter('invalid');
    }
}
