<?php

/**
 * Tests for the FormYesNo helper
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\Test\DomMatcherTrait;
use Laminas\I18n\View\Helper\Translate;
use Library\View\Helper\HtmlElement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests for the FormYesNo helper
 */
class FormYesNoTest extends AbstractTestCase
{
    use DomMatcherTrait;

    public static function invokeAttributesProvider()
    {
        return array(
            array(array(), array('method' => 'post')),
            array(array('foo' => 'bar'), array('foo' => 'bar', 'method' => 'post')),
            array(array('method' => 'get'), array('method' => 'get')),
            array(array('method' => 'get', 'foo' => 'bar'), array('method' => 'get', 'foo' => 'bar')),
        );
    }

    /**
     * @dataProvider invokeAttributesProvider
     */
    public function testInvoke($attributesOrig, $attributesUpdated)
    {
        /** @var Stub|Translate */
        $translate = $this->createMock(Translate::class);
        $translate->method('__invoke')->willReturnCallback(
            function ($message) {
                return "_($message)";
            }
        );

        /** @var MockObject|HtmlElement */
        $htmlElement = $this->createMock(HtmlElement::class);
        $htmlElement->expects($this->once())
            ->method('__invoke')
            ->with(
                'input',
                null,
                array(
                    'type' => 'hidden',
                    'name' => 'hiddenName',
                    'value' => 'hiddenValue',
                )
            )->willReturn('<input type="hidden" name="hiddenName" value="hiddenValue">');
        $htmlElement->method('htmlAttribs')->with($attributesUpdated)->willReturn(' method="_method"');

        $helper = new \Library\View\Helper\FormYesNo($translate, $htmlElement);

        $result = $helper('TestCaption', array('hiddenName' => 'hiddenValue'), $attributesOrig);
        $xPath = $this->createXpath($result);

        $this->assertXpathCount(1, $xPath, '//p[text()="TestCaption"]');
        $this->assertXpathCount(1, $xPath, '//form[@method="_method"]');
        $this->assertXpathCount(1, $xPath, '//input[@type="hidden"][@name="hiddenName"][@value="hiddenValue"]');
        $this->assertXpathCount(1, $xPath, '//input[@type="submit"][@name="yes"][@value="_(Yes)"]');
        $this->assertXpathCount(1, $xPath, '//input[@type="submit"][@name="no"][@value="_(No)"]');
    }
}
