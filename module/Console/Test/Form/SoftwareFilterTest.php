<?php
/**
 * Tests for SoftwareFilter
 *
 * Copyright (C) 2011-2019 Holger Schletz <holger.schletz@web.de>
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

/**
 * Tests for SoftwareFilter
 */
class SoftwareFilterTest extends \Console\Test\AbstractFormTest
{
    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\SoftwareFilter;
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertEquals('GET', $this->_form->getAttribute('method'));
        $filter = $this->_form->get('filter');
        $this->assertInstanceOf('Zend\Form\Element\Select', $filter);
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
        $this->_form->setFilter('accepted');
        $this->assertEquals('accepted', $this->_form->get('filter')->getValue());
    }

    public function testSetFilterIgnored()
    {
        $this->_form->setFilter('ignored');
        $this->assertEquals('ignored', $this->_form->get('filter')->getValue());
    }

    public function testSetFilterNew()
    {
        $this->_form->setFilter('new');
        $this->assertEquals('new', $this->_form->get('filter')->getValue());
    }

    public function testSetFilterAll()
    {
        $this->_form->setFilter('all');
        $this->assertEquals('all', $this->_form->get('filter')->getValue());
    }

    public function testSetFilterInvalid()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Invalid filter value: invalid');
        $this->_form->setFilter('invalid');
    }
}
