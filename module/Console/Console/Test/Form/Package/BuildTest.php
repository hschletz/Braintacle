<?php
/**
 * Tests for Build form
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

namespace Console\Test\Form\Package;

/**
 * Tests for Build form
 */
class BuildTest extends \Console\Test\AbstractFormTest
{
    protected function _getForm()
    {
        $packageManager = $this->getMockBuilder('Model\Package\PackageManager')
                               ->disableOriginalConstructor()
                               ->getMock();
        $packageManager->method('getAllNames')->willReturn(array('name1'));
        $form = new \Console\Form\Package\Build;
        $form->setOption('packageManager', $packageManager);
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('Name'));
        $this->assertInstanceOf('Zend\Form\Element\Textarea', $this->_form->get('Comment'));
        $this->assertInstanceOf('Zend\Form\Element\Select', $this->_form->get('Platform'));

        $action = $this->_form->get('DeployAction');
        $this->assertInstanceOf('Zend\Form\Element\Select', $action);
        $this->assertEquals('changeParam()', $action->getAttribute('onchange'));

        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('ActionParam'));
        $this->assertInstanceOf('Zend\Form\Element\File', $this->_form->get('File'));
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $this->_form->get('Priority'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->_form->get('MaxFragmentSize'));

        $warn = $this->_form->get('Warn');
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $warn);
        $this->assertEquals('form_package_build_warn', $warn->getAttribute('id'));
        $this->assertEquals('toggleWarn()', $warn->getAttribute('onchange'));

        $this->assertInstanceOf('\Zend\Form\Element\Textarea', $this->_form->get('WarnMessage'));
        $this->assertInstanceOf('\Zend\Form\Element\Text', $this->_form->get('WarnCountdown'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $this->_form->get('WarnAllowAbort'));
        $this->assertInstanceOf('\Zend\Form\Element\Checkbox', $this->_form->get('WarnAllowDelay'));
        $this->assertInstanceOf('\Zend\Form\Element\Textarea', $this->_form->get('PostInstMessage'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterInvalidNoPostData()
    {
        // Test empty POST data, can happen when post_max_size has been exceeded.
        // Form should be invalid and not generate any warnings.
        $this->_form->setData(array());
        $this->assertFalse($this->_form->isValid());
    }

    public function testInputFilterValidMinimal()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterStringTrim()
    {
        $data = array(
            'Name' => ' name2 ',
            'Comment' => ' comment ',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => ' param ', // not trimmed
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => ' 12 ',
            'Warn' => '0',
            'WarnMessage' => ' WarnMessage ',
            'WarnCountdown' => ' 34 ',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => ' PostInstMessage ',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertEquals('name2', $data['Name']);
        $this->assertEquals('comment', $data['Comment']);
        $this->assertEquals(' param ', $data['ActionParam']);
        $this->assertEquals('12', $data['MaxFragmentSize']);
        $this->assertEquals('WarnMessage', $data['WarnMessage']);
        $this->assertEquals('34', $data['WarnCountdown']);
        $this->assertEquals('PostInstMessage', $data['PostInstMessage']);
    }

    public function testInputFilterStringTrimWhitespaceOnly()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => ' ',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => ' ',
            'Warn' => '0',
            'WarnMessage' => ' ',
            'WarnCountdown' => ' ',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => ' ',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertSame('', $data['Comment']);
        $this->assertSame('', $data['MaxFragmentSize']);
        $this->assertSame('', $data['WarnMessage']);
        $this->assertSame('', $data['WarnCountdown']);
        $this->assertSame('', $data['PostInstMessage']);
    }

    public function testInputFilterInvalidRequiredFieldsMissing()
    {
        $data = array(
            'Name' => ' ',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => ' ',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(2, $messages);
        $this->assertArrayHasKey('isEmpty', $messages['Name']);
        $this->assertArrayHasKey('isEmpty', $messages['ActionParam']);
    }

    public function testInputFilterValidStringMaxLength()
    {
        $string = str_repeat("\xC3\x84", 255);
        $data = array(
            'Name' => $string,
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
    }

    public function testInputFilterValidStringTooLong()
    {
        $string = str_repeat("\xC3\x84", 256);
        $data = array(
            'Name' => $string,
            'Comment' => $string, // OK
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => $string, // OK
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => $string, // OK
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => $string, // OK
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('stringLengthTooLong', $messages['Name']);
    }

    public function testInputFilterInvalidNameExists()
    {
        $data = array(
            'Name' => ' NAME1 ', // Case insensitive
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('inArray', $messages['Name']);
    }

    public function testInputFilterInvalidFileMissingForStoreAction()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'store',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('fileUploadFileErrorNoFile', $messages['File']);
    }

    public function testInputFilterInvalidFileMissingForLaunchAction()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'launch',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertArrayHasKey('fileUploadFileErrorNoFile', $messages['File']);
    }

    public function testInputFilterValidFileUploaded()
    {
        // UploadFile validator does not work with vfsStream wrapper.
        // Use real temporary file.
        $tmpFile = tmpfile();
        $file = stream_get_meta_data($tmpFile)['uri'];
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'launch',
            'ActionParam' => 'param',
            'File' => array(
                'tmp_name' => $file,
                'name' => 'uploaded_file',
                'error' => UPLOAD_ERR_OK,
            ),
            'Priority' => '0',
            'MaxFragmentSize' => '',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        // UploadFile validator calls is_uploaded_file() which cannot easily be
        // mocked. Validation will fail. Test for correct error count and type.
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(1, $messages);
        $this->assertCount(1, $messages['File']);
        $this->assertArrayHasKey(\Zend\Validator\File\UploadFile::ATTACK, $messages['File']);
    }

    public function testInputFilterValidInteger()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => ' 1.234 ',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => ' 5.678 ',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $data = $this->_form->getData();
        $this->assertSame(1234, $data['MaxFragmentSize']);
        $this->assertSame(5678, $data['WarnCountdown']);
    }

    public function testInputFilterInvalidInteger()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '1a',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '2a',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages();
        $this->assertCount(2, $messages);
        $this->assertArrayHasKey('MaxFragmentSize', $messages);
        $this->assertArrayHasKey('WarnCountdown', $messages);
    }

    public function testSetDataIntegerValues()
    {
        $data = array(
            'Name' => 'name2',
            'Comment' => '',
            'Platform' => 'linux',
            'DeployAction' => 'execute',
            'ActionParam' => 'param',
            'File' => '',
            'Priority' => '0',
            'MaxFragmentSize' => '1234',
            'Warn' => '0',
            'WarnMessage' => '',
            'WarnCountdown' => '5678',
            'WarnAllowAbort' => '0',
            'WarnAllowDelay' => '0',
            'PostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setData($data);
        $this->assertSame('1.234', $this->_form->get('MaxFragmentSize')->getValue());
        $this->assertSame('5.678', $this->_form->get('WarnCountdown')->getValue());
    }

    public function testRender()
    {
        $view = $this->_createView();
        $output = $this->_form->render($view);
        $this->assertContains('</form>', $output);

        $headScript = $view->headScript()->toString();
        $this->assertContains(
            'var actionParamLabels = {"launch":"Befehlszeile","execute":"Befehlszeile","store":"Zielpfad"};',
            $headScript
        );
        $this->assertContains('function changeParam()', $headScript);
        $this->assertContains('function toggleWarn()', $headScript);

        $bodyOnLoad = $view->placeholder('BodyOnLoad');
        $this->assertContains('toggleWarn()', $bodyOnLoad);
        $this->assertContains('changeParam()', $bodyOnLoad);

    }
}
