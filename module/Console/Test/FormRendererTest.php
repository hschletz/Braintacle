<?php
/**
 * Form renderer test case
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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
     * Create a new view renderer
     *
     * @return \Zend\View\Renderer\PhpRenderer
     */
    protected function _createView()
    {
        $application = \Library\Application::init('Console', true);
        $view = new \Zend\View\Renderer\PhpRenderer;
        $view->setHelperPluginManager($application->getServiceManager()->get('ViewHelperManager'));
        return $view;
    }

    public function testRenderPostMaxSizeExceeded()
    {
        // Preserve global state
        $post = $_POST;
        $files = $_FILES;
        if (array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $requestMethod = $_SERVER['REQUEST_METHOD'];
        }

        // Simulate oversized POST request
        $_POST = array();
        $_FILES = array();
        $_SERVER['REQUEST_METHOD'] = 'post';

        $form = $this->getMockBuilder('Console\Form\Form')
                     ->setMethods(null)
                     ->getMock();

        $expected = <<<EOT
<p class="error">
Der Wert für post_max_size (%s) wurde überschritten.
</p>
<form action="" method="POST">
<div class="table"></div>

</form>

EOT;
        // Catch failure to allow cleanup
        try {
            $this->assertFalse($form->isValid());
            $this->assertEquals(
                sprintf($expected, ini_get('post_max_size')),
                $form->render($this->_createView())
            );
        } catch (\Exception $e) {
            // Just re-throw exception, cleanup is done later
            throw $e;
        } finally {
            // Cleanup
            $_POST = $post;
            $_FILES = $files;
            if (isset($requestMethod)) {
                $_SERVER['REQUEST_METHOD'] = $requestMethod;
            } else {
                unset($_SERVER['REQUEST_METHOD']);
            }
        }
    }

    /**
     * Test default form tags
     */
    public function testRenderWithCsrf()
    {
        $view = $this->_createView();

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
        $form->init();
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
        $view = $this->_createView();

        $form = $this->getMockBuilder('Console\Form\Form')
                     ->setMethods(array('renderFieldset'))
                     ->setMockClassName('Form_Mock')
                     ->getMock();
        $form->expects($this->once())
             ->method('renderFieldset')
             ->with($view, $form)
             ->will($this->returnValue('fieldset'));
        $form->init();
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
        $view = $this->_createView();
        $view->plugin('FormRow')->setTranslatorEnabled(false);

        $text1 = new \Zend\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->init();
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
        $view = $this->_createView();
        $view->plugin('FormRow')->setTranslatorEnabled(false);
        $view->plugin('FormLabel')->setTranslatorEnabled(false);

        $text1 = new \Zend\Form\Element\Text('text1');
        $text1->setLabel('Text1')->setAttribute('id', 'text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setLabel('Text2')->setAttribute('id', 'text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->init();
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
        $view = $this->_createView();

        $text1 = new \Zend\Form\Element\Text('text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setMessages(array('message'));
        $submit = new \Zend\Form\Element\Submit('submit');

        $form = new \Console\Form\Form;
        $form->init();
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

    public function testRenderFieldsetRenderFieldsetAsElement()
    {
        $translator = $this->getMock('Zend\I18n\Translator\Translator');
        $translator->method('translate')
                   ->willReturnCallback(function ($string) {
                       return "$string-translated";
                   });
        $view = $this->_createView();
        $view->plugin('translate')->setTranslator($translator);
        $view->plugin('FormRow')->setTranslatorEnabled(false);

        $text1 = new \Zend\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $text2 = new \Zend\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $fieldset = new \Zend\Form\Fieldset('fieldset');
        $fieldset->setLabel('Fieldset');
        $text3 = new \Zend\Form\Element\Text('text3');
        $text3->setLabel('Text3');
        $fieldset->add($text3);

        $form = new \Console\Form\Form;
        $form->init();
        $form->add($text1);
        $form->add($fieldset);
        $form->add($text2);

        $expected = <<<EOT
<div class="table">
<label><span>Text1</span><input type="text" name="text1" value=""></label>
<span class="label">Fieldset-translated</span>
<fieldset>
<legend></legend>
<div class="table"><label><span>Text3</span><input type="text" name="text3" value=""></label>
</div>

</fieldset>

<label><span>Text2</span><input type="text" name="text2" value=""></label>
</div>

EOT;
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }
}
