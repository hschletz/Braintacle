<?php

/**
 * Tests for Inventory form
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

namespace Console\Test\Form\Preferences;

class InventoryTest extends \Console\Test\AbstractFormTest
{
    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('inspectRegistry'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultMergeConfig'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultMergeCustomFields'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultMergeGroups'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultMergePackages'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultMergeProductKey'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultDeleteInterfaces'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testRender()
    {
        $view = $this->createView();
        $html = $this->_form->render($view);
        $document = new \Laminas\Dom\Document($html);
        $this->assertCount(
            1,
            \Laminas\Dom\Document\Query::execute(
                "//a[@href='/console/preferences/registryvalues/']" .
                "[text()='\n[Inventarisierte Registry-Werte verwalten]\n']",
                $document
            )
        );
    }
}
