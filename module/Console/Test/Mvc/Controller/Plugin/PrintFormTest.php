<?php

/**
 * Tests for PrintForm controller plugin
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

namespace Console\Test\Mvc\Controller\Plugin;

use Console\Form\Form;
use Console\Module;
use Console\Mvc\Controller\Plugin\PrintForm;
use Console\View\Helper\Form\FormHelperInterface;
use Laminas\Form\Form as BaseForm;
use Laminas\View\Helper\HelperInterface;
use Laminas\View\HelperPluginManager;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver\TemplateMapResolver;
use LogicException;

/**
 * Tests for PrintForm controller plugin
 */
class PrintFormTest extends \Library\Test\Mvc\Controller\Plugin\AbstractTest
{
    public function testViewModelWithoutHelper()
    {
        $form = $this->createStub(Form::class);

        $plugin = new PrintForm();

        $viewModel = $plugin($form);
        $this->assertInstanceOf(ViewModel::class, $viewModel);
        $this->assertEquals('plugin/PrintForm.php', $viewModel->getTemplate());
        $this->assertSame($form, $viewModel->form);
        $this->assertNull($viewModel->helperName);
    }

    public function testViewModelWithHelper()
    {
        $form = $this->createStub(Form::class);

        $plugin = new PrintForm();

        $viewModel = $plugin($form, 'helper_name');
        $this->assertInstanceOf(ViewModel::class, $viewModel);
        $this->assertEquals('plugin/PrintForm.php', $viewModel->getTemplate());
        $this->assertSame($form, $viewModel->form);
        $this->assertEquals('helper_name', $viewModel->helperName);
    }

    public function testTemplateWithUnsuitableForm()
    {
        $resolver = new TemplateMapResolver(['test' => Module::getPath('views/plugin/PrintForm.php')]);

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        $form = $this->createStub(BaseForm::class);

        $this->assertEquals(
            '',
            $renderer->render('test', ['form' => $form, 'helperName' => null])
        );
    }

    public function testTemplateWithLegacyRenderer()
    {
        $resolver = new TemplateMapResolver(['test' => Module::getPath('views/plugin/PrintForm.php')]);

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        $form = $this->createStub(Form::class);
        $form->method('render')->with($renderer)->willReturn('rendered form');

        $this->assertEquals(
            'rendered form',
            $renderer->render('test', ['form' => $form, 'helperName' => null])
        );
    }

    public function testTemplateWithViewHelper()
    {
        $resolver = new TemplateMapResolver(['test' => Module::getPath('views/plugin/PrintForm.php')]);

        $form = $this->createStub(Form::class);

        $helper = $this->createStub(FormHelperInterface::class);
        $helper->method('__invoke')->with($form)->willReturn('rendered form');

        $pluginManager = $this->createStub(HelperPluginManager::class);
        $pluginManager->method('get')->with('helper_name')->willReturn($helper);

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $renderer->setHelperPluginManager($pluginManager);

        $this->assertEquals(
            'rendered form',
            $renderer->render('test', ['form' => $form, 'helperName' => 'helper_name'])
        );
    }

    public function testTemplateWithUnsuitableViewHelper()
    {
        $resolver = new TemplateMapResolver(['test' => Module::getPath('views/plugin/PrintForm.php')]);

        $form = $this->createStub(Form::class);

        $helper = $this->createStub(HelperInterface::class);

        $pluginManager = $this->createStub(HelperPluginManager::class);
        $pluginManager->method('get')->with('helper_name')->willReturn($helper);

        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        $renderer->setHelperPluginManager($pluginManager);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'View helper passed to Printform plugin must implement ' . FormHelperInterface::class
        );

        $renderer->render('test', ['form' => $form, 'helperName' => 'helper_name']);
    }
}
