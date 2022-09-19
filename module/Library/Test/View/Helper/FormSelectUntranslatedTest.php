<?php

/**
 * Tests for the FormSelectUntranslated helper
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

namespace Library\Test\View\Helper;

/**
 * Tests for the FormSelectUntranslated helper
 */
class FormSelectUntranslatedTest extends AbstractTest
{
    public function testFormElementHelperIntegration()
    {
        $element = new \Laminas\Form\Element\Select('test');
        $element->setAttribute('type', 'select_untranslated')
                ->setValueOptions(array('Yes<b>', 'No'));
        $expected = <<<EOT
<select name="test"><option value="0">Yes&lt;b&gt;</option>
<option value="1">No</option></select>
EOT;
        $view = new \Laminas\View\Renderer\PhpRenderer();
        $view->setHelperPluginManager(static::$_helperManager);
        $helper = static::$_helperManager->get('formElement');
        $helper->setView($view);
        $this->assertEquals($expected, $helper($element));
    }
}
