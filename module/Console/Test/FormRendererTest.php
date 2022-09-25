<?php

/**
 * Form renderer test case
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

namespace Console\Test;

/**
 * Form renderer test case
 *
 * Tests for Console\Form\Form rendering capabilities
 */
class FormRendererTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Create a new view renderer
     *
     * @return \Laminas\View\Renderer\PhpRenderer
     */
    protected function createView()
    {
        $serviceManager = \Library\Application::init('Console')->getServiceManager();
        $serviceManager->setService('Library\UserConfig', array());
        $view = new \Laminas\View\Renderer\PhpRenderer();
        $view->setHelperPluginManager($serviceManager->get('ViewHelperManager'));
        return $view;
    }

    public function testRenderFieldsetWithoutId()
    {
        $view = $this->createView();
        $view->plugin('FormRow')->setTranslatorEnabled(false);

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $text2->setMessages(array('message'));
        $submit = new \Laminas\Form\Element\Submit('submit');

        $form = new \Console\Form\Form();
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
        $view = $this->createView();
        $view->plugin('FormRow')->setTranslatorEnabled(false);
        $view->plugin('FormLabel')->setTranslatorEnabled(false);

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text1->setLabel('Text1')->setAttribute('id', 'text1');
        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setLabel('Text2')->setAttribute('id', 'text2');
        $text2->setMessages(array('message'));
        $submit = new \Laminas\Form\Element\Submit('submit');

        $form = new \Console\Form\Form();
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
        $view = $this->createView();

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setMessages(array('message'));
        $submit = new \Laminas\Form\Element\Submit('submit');

        $form = new \Console\Form\Form();
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
        $translator = $this->createMock('Laminas\I18n\Translator\Translator');
        $translator->method('translate')
                   ->willReturnCallback(function ($string) {
                       return "$string-translated";
                   });
        $view = $this->createView();
        $view->plugin('translate')->setTranslator($translator);
        $view->plugin('FormRow')->setTranslatorEnabled(false);

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $fieldset = new \Laminas\Form\Fieldset('fieldset');
        $fieldset->setLabel('Fieldset');
        $text3 = new \Laminas\Form\Element\Text('text3');
        $text3->setLabel('Text3');
        $fieldset->add($text3);

        $form = new \Console\Form\Form();
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
