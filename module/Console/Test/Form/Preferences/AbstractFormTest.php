<?php

/**
 * Tests for AbstractForm
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

namespace Console\Test\Form\Preferences;

/**
 * Tests for AbstractForm
 */
class AbstractFormTest extends \PHPUnit\Framework\TestCase
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

    public function testInit()
    {
        $form = $this->getMockForAbstractClass('Console\Form\Preferences\AbstractForm');
        $form->init();
        $this->assertInstanceOf('Laminas\Form\Fieldset', $form->get('Preferences'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $form->get('Submit'));
    }

    public function testRenderFieldsetWithoutId()
    {
        $form = $this->getMockForAbstractClass('Console\Form\Preferences\AbstractForm');
        $form->init();
        $preferences = $form->get('Preferences');

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $preferences->add($text1);

        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $text2->setMessages(array('message'));
        $preferences->add($text2);

        $expected = <<<EOT
<div class='table'>
<label><span>Text1</span><input type="text" name="text1" value=""></label>
<label><span>Text2</span><input type="text" name="text2" class="input-error" value=""></label>
<div class='row'>
<span class='cell'></span>
<ul class="errors"><li>message</li></ul>
</div>
<div class='row'>
<span class='cell'></span>
<input type="submit" name="Submit" value="Setzen">
</div>
</div>

EOT;
        $view = $this->createView();
        $view->plugin('FormRow')->setTranslatorEnabled(false);
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }

    public function testRenderFieldsetRenderFieldsetAsElement()
    {
        $form = $this->getMockForAbstractClass('Console\Form\Preferences\AbstractForm');
        $form->init();
        $preferences = $form->get('Preferences');

        $text1 = new \Laminas\Form\Element\Text('text1');
        $text1->setLabel('Text1');
        $preferences->add($text1);

        $fieldset = new \Laminas\Form\Fieldset('fieldset');
        $fieldset->setLabel('Fieldset');
        $preferences->add($fieldset);

        $text3 = new \Laminas\Form\Element\Text('text3');
        $text3->setLabel('Text3');
        $fieldset->add($text3);

        $text2 = new \Laminas\Form\Element\Text('text2');
        $text2->setLabel('Text2');
        $preferences->add($text2);

        $expected = <<<EOT
<div class='table'>
<label><span>Text1</span><input type="text" name="text1" value=""></label>
<span class="label">Fieldset-translated</span>
<fieldset>
<legend></legend>
<div class="table"><label><span>Text3</span><input type="text" name="text3" value=""></label>
</div>

</fieldset>

<label><span>Text2</span><input type="text" name="text2" value=""></label>
<div class='row'>
<span class='cell'></span>
<input type="submit" name="Submit" value="Setzen">
</div>
</div>

EOT;
        $translator = $this->createMock('Laminas\I18n\Translator\Translator');
        $translator->method('translate')
                   ->willReturnCallback(function ($string) {
                       return "$string-translated";
                   });
        $view = $this->createView();
        $view->plugin('translate')->setTranslator($translator);
        $view->plugin('FormRow')->setTranslatorEnabled(false);
        $this->assertEquals($expected, $form->renderFieldset($view, $form));
    }
}
