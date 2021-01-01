<?php
/**
 * Tests for the Form helper
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
    protected function _getHelperName()
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

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('getView'))
                       ->getMock();
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

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethods(array('getView'))
                       ->getMock();
        $helper->method('getView')->willReturn($view);

        $this->assertEquals('exceeded', $helper->postMaxSizeExceeded());
    }

    public function testRender()
    {
        $form = $this->createMock('Laminas\Form\Form');
        $form->expects($this->once())->method('prepare');

        $view = $this->createMock('Laminas\View\Renderer\PhpRenderer');
        $view->method('__call')->with('consoleFormFieldset', array($form))->willReturn('content');

        $helper = $this->getMockBuilder($this->_getHelperClass())
                       ->disableOriginalConstructor()
                       ->setMethodsExcept(array('render'))
                       ->getMock();
        $helper->method('postMaxSizeExceeded')->willReturn('exceeded');
        $helper->method('openTag')->willReturn('<form>');
        $helper->method('getView')->willReturn($view);
        $helper->method('closeTag')->willReturn('</form>');

        $this->assertEquals('exceeded<form>content</form>', $helper->render($form));
    }
}
