<?php

/**
 * Tests for the Form helper
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

namespace Console\Test\View\Helper\Form;

use Console\View\Helper\Form\Form as FormHelper;
use Laminas\Form\Form;
use Laminas\Form\FormInterface;
use Laminas\View\Renderer\PhpRenderer;

class FormTest extends \Library\Test\View\Helper\AbstractTest
{
    protected $_postBackup;
    protected $_filesBackup;
    protected $_serverBackup;

    public function setUp(): void
    {
        parent::setUp();
        $this->_postBackup = $_POST;
        $this->_filesBackup = $_FILES;
        $this->_serverBackup = $_SERVER;
    }

    public function tearDown(): void
    {
        $_POST = $this->_postBackup;
        $_FILES = $this->_filesBackup;
        $_SERVER = $this->_serverBackup;
        parent::tearDown();
    }

    /** {@inheritdoc} */
    protected function getHelperName()
    {
        return 'consoleForm';
    }

    public function postMaxSizeExceededProvider()
    {
        return array(
            array(array(), array(), array('REQUEST_METHOD' => 'GET')),
            array(array(), array('file'), array('REQUEST_METHOD' => 'GET')),
            array(array(), array('file'), array('REQUEST_METHOD' => 'POST')),
            array(array('post'), array(), array('REQUEST_METHOD' => 'GET')),
            array(array('post'), array(), array('REQUEST_METHOD' => 'POST')),
            array(array('post'), array('file'), array('REQUEST_METHOD' => 'GET')),
            array(array('post'), array('file'), array('REQUEST_METHOD' => 'POST')),
        );
    }

    /** @dataProvider postMaxSizeExceededProvider */
    public function testPostMaxSizeExceededProviderNoError($post, $files, $server)
    {
        $_POST = $post;
        $_FILES = $files;
        $_SERVER = $server;

        $helper = $this->createPartialMock(FormHelper::class, ['getView']);
        $helper->expects($this->never())->method('getView');

        $this->assertEquals('', $helper->postMaxSizeExceeded());
    }

    public function testPostMaxSizeExceededProviderUploadError()
    {
        $_POST = array();
        $_FILES = array();
        $_SERVER = array('REQUEST_METHOD' => 'POST');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')
             ->willreturnMap([
                 ['translate', ['The post_max_size value of %s has been exceeded.'], 'exceeded %s'],
                 ['htmlElement', ['p', 'exceeded ' . ini_get('post_max_size'), ['class' => 'error']], 'exceeded']
             ]);

        $helper = $this->createPartialMock(FormHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('exceeded', $helper->postMaxSizeExceeded());
    }

    public function testRender()
    {
        $form = $this->createStub(FormInterface::class);

        $helper = $this->createPartialMock(FormHelper::class, ['renderForm']);
        $helper->expects($this->once())->method('renderForm')->with($form)->willReturn('rendered form');

        $this->assertEquals('rendered form', $helper->render($form));
    }

    public function testRenderForm()
    {
        $form = $this->createStub(FormInterface::class);

        $helper = $this->createPartialMock(
            FormHelper::class,
            ['prepareForm', 'postMaxSizeExceeded', 'openTag', 'renderContent', 'closeTag']
        );
        $helper->expects($this->once())->method('prepareForm')->with($form);
        $helper->method('postMaxSizeExceeded')->willReturn('exceeded');
        $helper->method('openTag')->with($form)->willReturn('<form>');
        $helper->method('renderContent')->with($form, 'extra')->willReturn('content');
        $helper->method('closeTag')->willReturn('</form>');

        $this->assertEquals('exceeded<form>content</form>', $helper->renderForm($form, 'extra'));
    }

    public function testRenderFormWithExtraArgs()
    {
        $form = $this->createStub(FormInterface::class);

        $helper = $this->createPartialMock(
            FormHelper::class,
            ['prepareForm', 'postMaxSizeExceeded', 'openTag', 'renderContent', 'closeTag']
        );
        $helper->expects($this->once())->method('prepareForm')->with($form);
        $helper->method('postMaxSizeExceeded')->willReturn('exceeded');
        $helper->method('openTag')->with($form)->willReturn('<form>');
        $helper->method('renderContent')->with($form)->willReturn('content');
        $helper->method('closeTag')->willReturn('</form>');

        $this->assertEquals('exceeded<form>content</form>', $helper->renderForm($form));
    }

    public function testRenderContent()
    {
        $form = $this->createMock(FormInterface::class);

        $view = $this->createMock(PhpRenderer::class);
        $view->method('__call')->with('consoleFormFieldset', [$form])->willReturn('content');

        $helper = $this->createPartialMock(FormHelper::class, ['getView']);
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('content', $helper->renderContent($form));
    }

    public function testPrepareFormWithoutPrepare()
    {
        $form = $this->createMock(FormInterface::class);
        // Cannot mock prepare because method does not exist in FormInterface.
        // Just assert that prepareForm() does not throw an error.

        $helper = new FormHelper();
        $helper->prepareForm($form);

        $this->assertTrue(true); // Dummy assertion to mark test as complete
    }

    public function testPrepareFormWithPrepare()
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())->method('prepare');

        $helper = new FormHelper();
        $helper->prepareForm($form);
    }
}
