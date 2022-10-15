<?php

/**
 * Tests for the HtmlElement helper
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

use Laminas\View\Renderer\PhpRenderer;
use Library\View\Helper\HtmlElement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests for the HtmlElement helper
 */
class HtmlElementTest extends AbstractTest
{
    public function testInvokeNewline()
    {
        $helper = $this->getHelper();

        $this->assertEquals("<a>\ncontent\n</a>\n", $helper('a', 'content'));
    }

    public function testInvokeInline()
    {
        $helper = $this->getHelper();

        $this->assertEquals('<a>content</a>', $helper('a', 'content', null, true));
    }

    public function testInvokeStringCast()
    {
        $helper = $this->getHelper();

        $this->assertEquals('<a>0</a>', $helper('a', 0, null, true));
    }

    public function testInvokeAttributes()
    {
        $attribs = array('attrib' => 'value');

        /** @var MockObject|HtmlElement|callable */
        $helper = $this->createPartialMock(HtmlElement::class, ['htmlAttribs']);
        $helper->method('htmlAttribs')->with($attribs)->willReturn(' attribs');

        $this->assertEquals(
            '<a attribs>content</a>',
            $helper(
                'a',
                'content',
                $attribs,
                true
            )
        );
    }

    public function invokeEmptyElementsProvider()
    {
        return [
            ['a', '<a></a>'],
            ['br', '<br>'],
        ];
    }

    /**
     * @dataProvider invokeEmptyElementsProvider
     */
    public function testInvokeEmptyElements($element, $output)
    {
        /** @var HtmlElement */
        $helper = $this->getHelper();
        $this->assertEquals($output, $helper($element, null, null, true));
    }

    public function testHtmlAttribs()
    {
        /** @var HtmlElement */
        $helper = $this->getHelper();
        $this->assertEquals(' foo="bar"', $helper->htmlAttribs(array('foo' => 'bar')));
    }
}
