<?php

/**
 * Tests for the FormatMessages helper
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

namespace Console\Test\View\Helper;

/**
 * Tests for the FormatMessages helper
 */
class FormatMessagesTest extends \Library\Test\View\Helper\AbstractTest
{
    /**
     * Tests for the __invoke() method
     */
    public function testInvoke()
    {
        $escapeHtml = $this->createMock('Laminas\View\Helper\EscapeHtml');
        $escapeHtml->expects($this->any())
                   ->method('__invoke')
                   ->willReturnCallback(function ($value) {
                       return "escape($value)";
                   });

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->expects($this->once())
                    ->method('__invoke')
                    ->with('a', 'escape(http://example.net)', array('href' => 'http://example.net'), true)
                    ->will($this->returnValue('Uri'));

        $translate = $this->createMock('Laminas\I18n\View\Helper\Translate');
        $translate->expects($this->any())
                  ->method('__invoke')
                  ->willReturnCallback(function ($value) {
                      return "translate($value)";
                  });

        $uri = $this->createMock('Laminas\Uri\Http');
        $uri->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue('http://example.net'));

        $messages = array(
            'message1',
            array('message2 %s' => 'arg'),
            array('message3 %s %s' => array('arg1', $uri))
        );
        $expected = array(
            'translate(message1)',
            'translate(message2 escape(arg))',
            'translate(message3 escape(arg1) Uri)'
        );
        $helper = new \Console\View\Helper\FormatMessages($escapeHtml, $htmlElement, $translate);
        $this->assertEquals($expected, $helper($messages));
    }
}
