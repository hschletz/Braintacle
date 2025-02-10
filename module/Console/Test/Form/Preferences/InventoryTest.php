<?php

/**
 * Tests for Inventory form
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Test\DomMatcherTrait;
use Console\Test\AbstractFormTestCase;

class InventoryTest extends AbstractFormTestCase
{
    use DomMatcherTrait;

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
        $xPath = $this->createXpath($html);
        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertXpathCount(
            1,
            $xPath,
            "//a[@href='/console/preferences/registryvalues/'][text()='\n[Inventarisierte Registry-Werte verwalten]\n']",
        );
    }
}
