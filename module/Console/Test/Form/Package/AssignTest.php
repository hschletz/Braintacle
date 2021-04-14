<?php

/**
 * Tests for Assign form
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\Package\Assign;
use Laminas\Dom\Document\Query;

/**
 * Tests for Assign form
 */
class AssignTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testSetDataNoPackages()
    {
        $data = [];

        $form = $this->createPartialMock(Assign::class, ['setPackages', 'populateValues']);
        $form->expects($this->once())->method('setPackages')->with([]);
        $form->expects($this->once())->method('populateValues')->with($data);

        $form->setData([]);
    }

    public function testSetDataWithPackages()
    {
        $data = [
            'Packages' => [
                'package1' => '0',
                'package2' => '1',
            ]
        ];

        $form = $this->createPartialMock(Assign::class, ['setPackages', 'populateValues']);
        $form->expects($this->once())->method('setPackages')->with(['package1', 'package2']);
        $form->expects($this->once())->method('populateValues')->with($data);

        $form->setData($data);
    }

    public function testSetPackages()
    {
        $this->assertFalse($this->_form->has('Packages'));

        $this->_form->setPackages(array('package1', 'package2'));
        $this->assertTrue($this->_form->has('Packages'));
        $packages = $this->_form->get('Packages');
        $this->assertCount(2, $packages);
        $package2 = $packages->get('package2');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $package2);
        $this->assertEquals('package2', $package2->getLabel());

        // Overwrite previously set packages
        $this->_form->setPackages(array());
        $this->assertTrue($this->_form->has('Packages'));
        $packages = $this->_form->get('Packages');
        $this->assertCount(0, $packages);
    }

    public function testRenderFieldsetNoPackages()
    {
        $view = $this->createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $this->assertEquals('', $html);
    }

    public function testRenderFieldsetEmptyPackages()
    {
        $this->_form->setPackages(array());
        $view = $this->createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $this->assertEquals('', $html);
    }

    public function testRenderFieldsetWithPackages()
    {
        $this->_form->setPackages(array('package1', 'package2'));
        $view = $this->createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(1, Query::execute('//div[@class="table"]', $document));
        $this->assertCount(1, Query::execute('//*[text()="package1"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="checkbox"][@name="package1"]', $document));
        $this->assertCount(1, Query::execute('//*[text()="package2"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="checkbox"][@name="package2"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
    }
}
