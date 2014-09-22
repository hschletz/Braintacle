<?php
/**
 * Form renderer test case
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

namespace Console\Test;

/**
 * Form renderer test case
 *
 * Tests for Console\Form\Form rendering capabilities
 */
class FormRendererTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test default form tags
     */
    public function testRenderWithCsrf()
    {
        $view = \Library\Application::getService('ViewManager')->getRenderer();

        $csrf = $this->getMockBuilder('Zend\Form\Element\Csrf')
                     ->setMethods(array('getValue'))
                     ->setConstructorArgs(array('_csrf'))
                     ->getMock();
        $csrf->expects($this->once())
             ->method('getValue')
             ->will($this->returnValue('csrf'));

        $form = $this->getMockBuilder('Console\Form\Form')
                     ->setMethods(array('renderFieldset'))
                     ->setMockClassName('Form_Mock')
                     ->getMock();
        $form->expects($this->once())
             ->method('renderFieldset')
             ->with($view, $form)
             ->will($this->returnValue('fieldset'));
        $form->remove('_csrf');
        $form->add($csrf);

        $expected = <<<EOT
<form action="" method="POST" class="form&#x20;mock" id="mock">
<div><input type="hidden" name="_csrf" value="csrf"></div>
fieldset
</form>

EOT;
        $this->assertEquals($expected, $form->render($view));
    }

    public function testRenderWithoutCsrf()
    {
        $view = \Library\Application::getService('ViewManager')->getRenderer();

        $form = $this->getMockBuilder('Console\Form\Form')
                     ->setMethods(array('renderFieldset'))
                     ->setMockClassName('Form_Mock')
                     ->getMock();
        $form->expects($this->once())
             ->method('renderFieldset')
             ->with($view, $form)
             ->will($this->returnValue('fieldset'));
        $form->remove('_csrf');

        $expected = <<<EOT
<form action="" method="POST" class="form&#x20;mock" id="mock">
fieldset
</form>

EOT;
        $this->assertEquals($expected, $form->render($view));
    }

    public function testRenderFieldsetWithoutId()
    {
        $view = \Library\Application::getService('ViewManager')->getRenderer();

        $text1 = new \Zend\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->add($text1);
        $form->add($text2);
        $form->add($submit);

        $expected = <<<EOT
<div class="table">
<label><span>Text1</span><input type="text" name="text1" value=""></label>
<label><span>Text2</span><input type="text" name="text2" class="input-error" value=""></label>
<span class='cell'></span>
<ul class="errors"><li>message</li></ul>
<span class='cell'></span>
<input type="submit" name="submit" value="">
</div>

EOT;
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }

    public function testRenderFieldsetWithId()
    {
        $view = \Library\Application::getService('ViewManager')->getRenderer();

        $text1 = new \Zend\Form\Element\Text('text1');
        $text1->setLabel('Text1')->setAttribute('id', 'text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setLabel('Text2')->setAttribute('id', 'text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->add($text1);
        $form->add($text2);
        $form->add($submit);

        $expected = <<<EOT
<div class="table">
<div class='row'>
<label for="text1">Text1</label><input type="text" name="text1" id="text1" value="">
</div>
<div class='row'>
<label for="text2">Text2</label><input type="text" name="text2" id="text2" class="input-error" value="">
<span class='cell'></span>
<ul class="errors"><li>message</li></ul>
</div>
<span class='cell'></span>
<input type="submit" name="submit" value="">
</div>

EOT;
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }

    public function testRenderFieldsetWithoutLabel()
    {
        $view = \Library\Application::getService('ViewManager')->getRenderer();

        $text1 = new \Zend\Form\Element\Text('text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->add($text1);
        $form->add($text2);
        $form->add($submit);

        $expected = <<<EOT
<div class="table">
<div class='row'>
<span class='label'></span>
<input type="text" name="text1" value="">
</div>
<div class='row'>
<span class='label'></span>
<input type="text" name="text2" class="input-error" value="">
</div>
<span class='cell'></span>
<ul class="errors"><li>message</li></ul>
<span class='cell'></span>
<input type="submit" name="submit" value="">
</div>

EOT;
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }
}
