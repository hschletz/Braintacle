<?php
/**
 * Tests for the HtmlTag helper
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 * Tests for the HtmlTag helper
 */
class HtmlTagTest extends AbstractTest
{
    /**
     * Tests for the __invoke() method
     */
    public function testInvoke()
    {
        $escapeHtmlAttr = $this->_getHelper('escapeHtmlAttr');

        // Start tests with non-XHTML doctype
        $helper = new \Library\View\Helper\HtmlTag($escapeHtmlAttr, false);

        // Empty element, non-inline
        $this->assertEquals("<element>\n", $helper('element'));

        // Empty element, inline
        $this->assertEquals('<element>', $helper('element', null, null, true));

        // Empty element with escaped attribute, inline
        $this->assertEquals(
            '<element attribute="value&quot;value">',
            $helper(
                'element',
                null,
                array('attribute' => 'value"value'),
                true
            )
        );

        // Element with content, inline
        $this->assertEquals(
            '<element>content</element>',
            $helper('element', 'content', null, true)
        );

        // Empty XHTML Element, inline
        $helper = new \Library\View\Helper\HtmlTag($escapeHtmlAttr, true);
        $this->assertEquals('<element />', $helper('element', null, null, true));
    }
}
