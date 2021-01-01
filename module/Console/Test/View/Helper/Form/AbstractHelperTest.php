<?php
/**
 * Tests for the AbstractHelper
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

namespace Console\Test\View\Helper\Form;

class AbstractHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testRenderWithoutPrepare()
    {
        $form = $this->createMock('\Laminas\Form\FormInterface');

        $helper = $this->getMockBuilder('\Console\View\Helper\Form\AbstractHelper')
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(array('render'))
                       ->getMock();
        $helper->method('postMaxSizeExceeded')->willReturn("postMaxSizeExceeded\n");
        $helper->method('openTag')->with($form)->willReturn('openTag');
        $helper->method('renderElements')->with($form)->willReturn('renderElements');
        $helper->method('closeTag')->willReturn('closeTag');

        $this->assertEquals("postMaxSizeExceeded\nopenTag\nrenderElements\ncloseTag\n", $helper->render($form));
    }

    public function testRenderWithPrepare()
    {
        $form = $this->createMock('\Laminas\Form\Form');
        $form->expects($this->once())->method('prepare');

        $helper = $this->getMockBuilder('\Console\View\Helper\Form\AbstractHelper')
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(array('render'))
                       ->getMock();
        $helper->render($form);
    }
}
