<?php

/**
 * Tests for GroupMemberships form
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

use Console\Form\GroupMemberships;
use Laminas\Dom\Document\Query;

/**
 * Tests for GroupMemberships form
 */
class GroupMembershipsTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testSetDataNoGroups()
    {
        $form = $this->createPartialMock(GroupMemberships::class, ['setGroups', 'populateValues']);
        $form->expects($this->once())->method('setGroups')->with([]);
        $form->expects($this->once())->method('populateValues')->with([]);

        $form->setData([]);
    }

    public function testSetDataWithGroups()
    {
        $data = ['Groups' => ['group1' => '0', 'group2' => '1']];
        $form = $this->createPartialMock(GroupMemberships::class, ['setGroups', 'populateValues']);
        $form->expects($this->once())->method('setGroups')->with(['group1', 'group2']);
        $form->expects($this->once())->method('populateValues')->with($data);

        $form->setData($data);
    }

    public function testSetGroups()
    {
        $this->assertFalse($this->_form->has('Groups'));

        $form = new GroupMemberships();
        $form->setGroups(['group1', 'group2']);
        $this->assertTrue($form->has('Groups'));
        $groups = $form->get('Groups');
        $this->assertCount(2, $groups);
        $group2 = $groups->get('group2');
        $this->assertInstanceOf('Laminas\Form\Element\Radio', $group2);
        $this->assertEquals('group2', $group2->getName());
        $this->assertEquals('group2', $group2->getLabel());

        // Overwrite previously set groups
        $form->setGroups([]);
        $this->assertTrue($form->has('Groups'));
        $groups = $form->get('Groups');
        $this->assertCount(0, $groups);
    }

    public function testRenderFieldsetNoGroups()
    {
        $view = $this->createView();
        $html = $this->_form->renderFieldset($view, $this->_form);
        $this->assertEquals('', $html);
    }

    public function testRenderFieldsetEmptyGroups()
    {
        $form = new GroupMemberships();
        $form->setGroups([]);
        $view = $this->createView();
        $html = $form->renderFieldset($view, $form);
        $this->assertEquals('', $html);
    }

    public function testRenderFieldsetWithGroups()
    {
        $form = new GroupMemberships();
        $form->init();
        $form->setGroups(['group1', 'group2']);
        $form->prepare();
        $view = $this->createView();
        $html = $form->renderFieldset($view, $form);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(1, Query::execute('//div', $document));
        $this->assertCount(
            1,
            Query::execute(
                "//fieldset/legend/a[@href='/console/group/general/?name=group1'][text()='\ngroup1\n']",
                $document
            )
        );
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group1]"][@value="0"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group1]"][@value="1"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group1]"][@value="2"]', $document));
        $this->assertCount(
            1,
            Query::execute(
                "//fieldset/legend/a[@href='/console/group/general/?name=group2'][text()='\ngroup2\n']",
                $document
            )
        );
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group2]"][@value="0"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group2]"][@value="1"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="radio"][@name="Groups[group2]"][@value="2"]', $document));
        $this->assertCount(1, Query::execute('//input[@type="submit"]', $document));
    }
}
