<?php
/**
 * Tests for the ManageRegistryValues Helper
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

class SoftwareTest extends \Library\Test\View\Helper\AbstractTest
{
    /** {@inheritdoc} */
    protected function _getHelperName()
    {
        return 'consoleFormSoftware';
    }

    public function testInvoke()
    {
        $csrf = $this->createMock('Zend\Form\Element\Csrf');
        $softwareFieldset = $this->createMock('Zend\Form\Fieldset');

        $form = $this->createMock('Console\Form\Software');
        $form->expects($this->at(0))->method('prepare');
        $form->expects($this->at(1))->method('get')->with('_csrf')->willReturn($csrf);
        $form->expects($this->at(2))->method('get')->with('Software')->willReturn($softwareFieldset);

        $consoleForm = $this->createMock('Console\View\Helper\Form\Form');
        $consoleForm->method('postMaxSizeExceeded')->willReturn('EXCEEDED');
        $consoleForm->method('openTag')->with($form)->willReturn('<form>');
        $consoleForm->method('closeTag')->willReturn('</form>');

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->method('__invoke')->with($csrf)->willReturn('<csrf>');

        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $view->method('plugin')->willReturnMap([
            ['consoleForm', null, $consoleForm],
            ['formRow', null, $formRow],
        ]);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['__invoke'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);
        $helper->method('renderButtons')->with($form, 'filter')->willReturn('<buttons>');
        $helper->method('renderSoftwareFieldset')
               ->with($softwareFieldset, 'software', 'sorting')
               ->willReturn('<software>');

        $this->assertEquals(
            'EXCEEDED<form><csrf><buttons><software></form>',
            $helper($form, 'software', 'sorting', 'filter')
        );
    }

    public function renderButtonsProvider()
    {
        return [
            ['accepted', "\nIGNORE"],
            ['ignored', "ACCEPT\n"],
            ['', "ACCEPT\nIGNORE"],
        ];
    }

    /** @dataProvider renderButtonsProvider */
    public function testRenderButtons($filter, $buttons)
    {
        $accept = $this->createMock('Zend\Form\ElementInterface');
        $ignore = $this->createMock('Zend\Form\ElementInterface');

        $fieldset = $this->createMock('Console\Form\Software');
        $fieldset->method('get')->willReturnMap([
            ['Accept', $accept],
            ['Ignore', $ignore],
        ]);

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->method('__invoke')->willReturnMap([
            [$accept, null, null, null, 'ACCEPT'],
            [$ignore, null, null, null, 'IGNORE'],
        ]);

        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');
        $view->method('plugin')->with('formRow')->willReturn($formRow);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['renderButtons'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            "<div class='textcenter'>\n$buttons</div>\n",
            $helper->renderButtons($fieldset, $filter)
        );
    }

    public function testRenderSoftwareFieldset()
    {
        $view = $this->createMock('Zend\View\Renderer\PhpRenderer');

        $checkbox = $this->createMock('\Zend\Form\ElementInterface');

        $fieldset = $this->createMock('Zend\Form\FieldsetInterface');
        $fieldset->method('get')
                 ->with('c29mdHdhcmVfbmFtZQ==') // 'software_name'
                 ->willReturn($checkbox);

        $software = [
            ['name' => 'software_name', 'num_clients' => 42],
        ];

        $formRow = $this->createMock('Zend\Form\View\Helper\FormRow');
        $formRow->expects($this->at(0))->method('isTranslatorEnabled')->willReturn('translatorEnabled');
        $formRow->expects($this->at(1))->method('setTranslatorEnabled')->with(false);
        $formRow->expects($this->at(2))->method('__invoke')
                                       ->with($checkbox, \Zend\Form\View\Helper\FormRow::LABEL_APPEND)
                                       ->willReturn('checkbox');
        $formRow->expects($this->at(3))->method('setTranslatorEnabled')->with('translatorEnabled');

        $translate = $this->createMock('Zend\I18n\View\Helper\Translate');
        $translate->method('__invoke')->willReturnMap([
            ['Name', null, null, 'NAME'],
            ['Count', null, null, 'COUNT'],
        ]);

        $consoleUrl = $this->createMock('Library\View\Helper\HtmlElement');
        $consoleUrl->method('__invoke')->with(
            'client',
            'index',
            [
                'columns' => 'Name,UserName,LastContactDate,InventoryDate,Software.Version',
                'jumpto' => 'software',
                'filter' => 'Software',
                'search' => 'software_name',
            ]
        )->willReturn('url');

        $htmlElement = $this->createMock('Library\View\Helper\HtmlElement');
        $htmlElement->method('__invoke')->with('a', 42, ['href' => 'url'])->willReturn('link');

        $table = $this->createMock('Console\View\Helper\Table');
        $table->method('__invoke')
             ->with(
                 $software,
                 [
                     'name' => 'NAME',
                     'num_clients' => 'COUNT'
                 ],
                 'sorting',
                 $this->callback(function ($subject) use ($view, $software) {
                    if (count($subject) != 2) {
                        return false;
                    }
                    $checkbox = $subject['name']($view, $software[0]);
                    if ($checkbox != 'checkbox') {
                        printf("\nnname callback expected 'checkbox', got: %s\n", var_export($checkbox, true));
                        return false;
                    }
                    $link = $subject['num_clients']($view, $software[0]);
                    if ($link != 'link') {
                        printf("\nnum_clients callback expected 'link', got: %s\n", var_export($link, true));
                        return false;
                    }
                    return true;
                 }),
                 ['num_clients' => 'textright']
             )->willReturn('softwareFieldset');

        $view->method('plugin')->willReturnMap([
            ['formRow', null, $formRow],
            ['translate', null, $translate],
            ['table', null, $table],
            ['htmlElement', null, $htmlElement],
            ['consoleUrl', null, $consoleUrl],
        ]);

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(['renderSoftwareFieldset'])
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals(
            'softwareFieldset',
            $helper->renderSoftwareFieldset($fieldset, $software, 'sorting')
        );
    }
}
