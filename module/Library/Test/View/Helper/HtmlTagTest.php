<?php
/**
 * Tests for the HtmlTag helper
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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

        // Start tests with no doctype - assume HTML4.
        $doctype = new \Zend\View\Helper\Doctype;
        $helper = new \Library\View\Helper\HtmlTag($escapeHtmlAttr, $doctype);
        // Empty br element, non-inline
        $this->assertEquals("<br>\n", $helper('br'));

        // Empty br element, inline
        $this->assertEquals('<br>', $helper('br', null, null, true));

        // Empty br element with escaped attribute, inline
        $this->assertEquals(
            '<br attribute="value&quot;value">',
            $helper(
                'br',
                null,
                array('attribute' => 'value"value'),
                true
            )
        );

        // Element with integer content '0' (tests sideeffects from PHP's type juggling), inline
        $this->assertEquals(
            '<element>0</element>',
            $helper('element', 0, null, true)
        );

        // Empty string as content (tests sideeffects from PHP's type juggling), regardless of type
        $this->assertEquals('<br></br>', $helper('br', '', null, true));

        // Empty command element (HTML5 only) should get closing tag
        $this->assertEquals("<command></command>\n", $helper('command'));

        // Test empty HTML5 elements
        $doctype->setDoctype('HTML5');
        $helper = new \Library\View\Helper\HtmlTag($escapeHtmlAttr, $doctype);
        $this->assertEquals("<command>\n", $helper('command'));
        $this->assertEquals("<br>\n", $helper('br'));
        $this->assertEquals("<a></a>\n", $helper('a'));

        // Empty XHTML Elements
        $doctype->setDoctype('XHTML11');
        $helper = new \Library\View\Helper\HtmlTag($escapeHtmlAttr, $doctype);
        $this->assertEquals("<command />\n", $helper('command'));
        $this->assertEquals("<br />\n", $helper('br'));
        $this->assertEquals("<a />\n", $helper('a'));
    }
}
