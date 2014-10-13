<?php
/**
 * Tests for PackageController
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

namespace Console\Test\Controller;

/**
 * Tests for PackageController
 */
class PackageControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Package mock
     * @var \Model_Package
     */
    protected $_package;

    /**
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Build form mock
     * @var \Console\Form\Package\Build
     */
    protected $_buildForm;

    /**
     * Update form mock
     * @var \Console\Form\Package\Update
     */
    protected $_updateForm;

    /**
     * Set up mock objects
     */
    public function setUp()
    {
        $this->_package = $this->getMock('Model_Package');
        $this->_config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $this->_buildForm = $this->getMock('Console\Form\Package\Build');
        $this->_updateForm = $this->getMock('Console\Form\Package\Update');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\PackageController(
            $this->_package,
            $this->_config,
            $this->_buildForm,
            $this->_updateForm
        );
    }

    /** {@inheritdoc} */
    public function testService()
    {
        $this->_overrideService('Console\Form\Package\Build', $this->_buildForm);
        $this->_overrideService('Console\Form\Package\Update', $this->_updateForm);
        parent::testService();
    }

    public function testIndexActionPackageList()
    {
        $packages = array(
            array(
                'Name' => 'name1',
                'Comment' => 'comment1',
                'Timestamp' => new \Zend_Date('2014-03-29 20:03:45'),
                'Size' => 12345678,
                'Platform' => 'platform',
                'NumNonnotified' => 1,
                'NumSuccess' => 2,
                'NumNotified' => 3,
                'NumError' => 4,
            ),
            array(
                'Name' => 'name2',
                'Comment' => '',
                'Timestamp' => new \Zend_Date('2014-03-29 20:15:43'),
                'Size' => 87654321,
                'Platform' => 'platform',
                'NumNonnotified' => 0,
                'NumSuccess' => 0,
                'NumNotified' => 0,
                'NumError' => 0,
            ),
        );
        $this->_package->expects($this->once())
                       ->method('fetchAll')
                       ->will($this->returnValue($packages));
        $this->dispatch('/console/package/index/');
        $this->assertResponseStatusCode(200);

        // Name column
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/package/update/?name=name1"][@title="comment1"]',
            'name1'
        );
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/package/update/?name=name2"][not(@title)]',
            'name2'
        );

        // Timestamp column
        $this->assertXpathQueryContentContains(
            '//td',
            "\n29.03.14 20:03\n"
        );

        // Size column
        $this->assertXpathQueryContentContains(
            '//td[@class="textright"]',
            "\n11,8\xC2\xA0MB\n" // UTF-8 representation of &nbsp;
        );

        // Platform column
        $this->assertXpathQueryCount(
            "//td[text()='\nPlatform\n']",
            2
        );

        // Hyperlinks and classes for Num* columns
        $query = '//td[@class="textright"]/a[@href="/console/computer/index/' .
                 '?columns=Name,UserName,LastContactDate,InventoryDate&jumpto=software&filter=%s&search=%s' .
                 '"][@class="%s"]';

        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageNonnotified', 'name1', 'package_notnotified'),
            '1'
        );
        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageSuccess', 'name1', 'package_success'),
            '2'
        );
        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageNotified', 'name1', 'package_inprogress'),
            '3'
        );
        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageError', 'name1', 'package_error'),
            '4'
        );

        // Num* columns with '0' content
        $this->assertXpathQueryCount(
            "//td[@class='textright'][text()='\n0\n']",
            4
        );

        // 'Delete' column
        $this->assertXpathQueryContentContains(
            '//td/a[@href="/console/package/delete/?name=name1"]',
            'Delete'
        );

        // No flash messages
        $this->assertNotXpathQuery('//ul');
    }

    public function testIndexActionPackageFlashMessages()
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $flashMessenger->addErrorMessage('error');
        $flashMessenger->addSuccessMessage('success');
        $flashMessenger->addInfoMessage('info');
        $flashMessenger->setNamespace('packageName')->addMessage('<br>');

        $this->_package->expects($this->once())
                       ->method('fetchAll')
                       ->will($this->returnValue(array()));
        $this->dispatch('/console/package/index/');
        $this->assertResponseStatusCode(200);

        $this->assertXpathQueryContentContains(
            '//ul[@class="error"]/li',
            'error'
        );
        $this->assertXpathQueryContentContains(
            '//ul[@class="success"]/li',
            'success'
        );
        $this->assertXpathQueryContentContains(
            '//ul[@class="info"]/li',
            'info'
        );
    }

    public function testIndexActionPackageHighlightCurrentPackage()
    {
        $packages = array(
            array(
                'Name' => 'name1',
                'Comment' => 'comment1',
                'Timestamp' => new \Zend_Date('2014-03-29 20:03:45'),
                'Size' => 12345678,
                'Platform' => 'platform',
                'NumNonnotified' => 1,
                'NumSuccess' => 2,
                'NumNotified' => 3,
                'NumError' => 4,
            ),
            array(
                'Name' => 'name2',
                'Comment' => '',
                'Timestamp' => new \Zend_Date('2014-03-29 20:15:43'),
                'Size' => 87654321,
                'Platform' => 'platform',
                'NumNonnotified' => 0,
                'NumSuccess' => 0,
                'NumNotified' => 0,
                'NumError' => 0,
            ),
        );
        $this->_getControllerPlugin('FlashMessenger')->setNamespace('packageName')->addMessage('name1');
        $this->_package->expects($this->once())
                       ->method('fetchAll')
                       ->will($this->returnValue($packages));
        $this->dispatch('/console/package/index/');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQueryCount(
            '//tr[@class="highlight"]',
            1
        );
        $this->assertXpathQueryContentContains(
            '//tr[@class="highlight"]/td/a',
            'name1'
        );
    }

    public function testBuildActionGet()
    {
        $data = array(
            'Platform' => 'defaultPlatform',
            'DeployAction' => 'defaultAction',
            'ActionParam' => 'defaultActionParam',
            'Priority' => 'defaultPackagePriority',
            'MaxFragmentSize' => 'defaultMaxFragmentSize',
            'Warn' => 'defaultWarn',
            'WarnMessage' => 'defaultWarnMessage',
            'WarnCountdown' => 'defaultWarnCountdown',
            'WarnAllowAbort' => 'defaultWarnAllowAbort',
            'WarnAllowDelay' => 'defaultWarnAllowDelay',
            'PostInstMessage' => 'defaultPostInstMessage',
        );
        $this->_config->expects($this->exactly(11))
                      ->method('__get')
                      ->will($this->returnArgument(0));
        $this->_buildForm->expects($this->once())
                         ->method('setData')
                         ->with($data);
        $this->_buildForm->expects($this->never())
                         ->method('getData');
        $this->_buildForm->expects($this->never())
                         ->method('isValid');
        $this->_buildForm->expects($this->once())
                         ->method('render')
                         ->willReturn('<form></form>');
        $this->_package->expects($this->never())
                       ->method('build');
        $this->dispatch('/console/package/build');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testBuildActionPostInvalid()
    {
        $postData = array('Name' => 'packageName');
        $this->_buildForm->expects($this->once())
                         ->method('setData')
                         ->with($postData);
        $this->_buildForm->expects($this->never())
                         ->method('getData');
        $this->_buildForm->expects($this->once())
                         ->method('isValid')
                         ->willReturn(false);
        $this->_buildForm->expects($this->once())
                         ->method('render')
                         ->willReturn('<form></form>');
        $this->_package->expects($this->never())
                       ->method('build');
        $this->_package->expects($this->never())
                       ->method('build');
        $this->dispatch('/console/package/build', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testBuildActionPostValidSuccessWithPostinst()
    {
        $postData = array(
            'Name' => 'packageName',
            'PostInstMessage' => 'postinst',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Name' => 'packageName',
            'PostInstMessage' => 'postinst',
            'UserActionRequired' => true,
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
            'FileType' => 'file_type',
        );
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $formData = $postData + array('File' => $fileSpec);
        $this->_buildForm->expects($this->once())
                         ->method('setData')
                         ->with($formData);
        $this->_buildForm->expects($this->once())
                         ->method('getData')
                         ->willReturn($formData);
        $this->_buildForm->expects($this->once())
                         ->method('isValid')
                         ->willReturn(true);
        $this->_buildForm->expects($this->never())
                         ->method('render');
        $this->_testBuildPackage('/console/package/build', $postData, $packageData, true);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testBuildActionPostValidErrorWithoutPostinst()
    {
        $postData = array(
            'Name' => 'packageName',
            'PostInstMessage' => '',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Name' => 'packageName',
            'PostInstMessage' => '',
            'UserActionRequired' => false,
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
            'FileType' => 'file_type',
        );
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $formData = $postData + array('File' => $fileSpec);
        $this->_buildForm->expects($this->once())
                         ->method('setData')
                         ->with($formData);
        $this->_buildForm->expects($this->once())
                         ->method('getData')
                         ->willReturn($formData);
        $this->_buildForm->expects($this->once())
                         ->method('isValid')
                         ->willReturn(true);
        $this->_buildForm->expects($this->never())
                         ->method('render');
        $this->_testBuildPackage('/console/package/build', $postData, $packageData, false);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testDeleteActionGet()
    {
        $this->_package->expects($this->never())
                       ->method('fromName');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->dispatch('/console/package/delete/?name=Name');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'Name'", $this->getResponse()->getContent());
    }

    public function testDeleteActionPostNo()
    {
        $this->_package->expects($this->never())
                       ->method('fromName');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->dispatch('/console/package/delete/?name=Name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('Name')
                       ->will($this->returnValue(true));
        $this->_testDeletePackage('/console/package/delete/?name=Name', array('yes' => 'Yes'), 'Name', true);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testDeleteActionPostYesError()
    {
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('Name')
                       ->will($this->returnValue(false));
        $this->_testDeletePackage('/console/package/delete/?name=Name', array('yes' => 'Yes'), 'Name', false);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testUpdateActionGet()
    {
        $formData = array(
            'Deploy' => array(
                'Nonnotified' => 'defaultDeployNonnotified',
                'Success' => 'defaultDeploySuccess',
                'Notified' => 'defaultDeployNotified',
                'Error' => 'defaultDeployError',
                'Groups' => 'defaultDeployGroups',
            ),
            'Name' => 'Name',
            'Comment' => 'Comment',
            'Platform' => 'Platform',
            'DeployAction' => 'DeployAction',
            'ActionParam' => 'ActionParam',
            'Priority' => 'Priority',
            'MaxFragmentSize' => 'defaultMaxFragmentSize',
            'Warn' => 'Warn',
            'WarnMessage' => 'WarnMessage',
            'WarnCountdown' => 'WarnCountdown',
            'WarnAllowAbort' => 'WarnAllowAbort',
            'WarnAllowDelay' => 'WarnAllowDelay',
            'PostInstMessage' => 'PostInstMessage',
        );
        $this->_config->expects($this->exactly(6))
                      ->method('__get')
                      ->will($this->returnArgument(0));
        $this->_updateForm->expects($this->once())
                          ->method('setData')
                          ->with($formData);
        $this->_updateForm->expects($this->never())
                          ->method('getData');
        $this->_updateForm->expects($this->never())
                          ->method('isValid');
        $this->_updateForm->expects($this->once())
                          ->method('render')
                          ->willReturn('<form></form>');
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('oldName')
                       ->willReturn(true);
        $this->_package->expects($this->exactly(12))
                       ->method('offsetGet')
                       ->will($this->returnArgument(0));
        $this->_package->expects($this->never())
                       ->method('build');
        $this->_package->expects($this->never())
                       ->method('updateComputers');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->dispatch('/console/package/update/?name=oldName');
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testUpdateActionPostInvalid()
    {
        $postData = array('Name' => 'newName');
        $this->_updateForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_updateForm->expects($this->never())
                          ->method('getData');
        $this->_updateForm->expects($this->once())
                          ->method('isValid')
                          ->willReturn(false);
        $this->_updateForm->expects($this->once())
                          ->method('render')
                          ->willReturn('<form></form>');
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('oldName')
                       ->will($this->returnValue(true));
        $this->_package->expects($this->never())
                       ->method('build');
        $this->_package->expects($this->never())
                       ->method('updateComputers');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->dispatch('/console/package/update/?name=oldName', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testUpdateActionPostValidBuildSuccess()
    {
        $postData = array(
            'Deploy' => array(
                'Nonnotified' => '1',
                'Success' => '0',
                'Notified' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'PostInstMessage' => '',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Deploy' => array(
                'Nonnotified' => '1',
                'Success' => '0',
                'Notified' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'PostInstMessage' => '',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'UserActionRequired' => false,
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
            'FileType' => 'file_type',
        );
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $formData = $postData + array('File' => $fileSpec);
        $this->_updateForm->expects($this->once())
                          ->method('setData')
                          ->with($formData);
        $this->_updateForm->expects($this->once())
                          ->method('getData')
                          ->willReturn($formData);
        $this->_updateForm->expects($this->once())
                          ->method('isValid')
                          ->willReturn(true);
        $this->_updateForm->expects($this->never())
                          ->method('render');
        $this->_package->expects($this->exactly(2))
                       ->method('fromName')
                       ->with('oldName')
                       ->will($this->returnValue(true));
        $this->_package->expects($this->once())
                       ->method('updateComputers')
                       ->with($this->isInstanceOf('Model_Package', true, false, true, false, true));
        $this->_package->expects($this->once())
                       ->method('delete')
                       ->will($this->returnValue(true));
        $this->_testBuildPackage(
            '/console/package/update/?name=oldName',
            $postData,
            $packageData,
            true,
            array(
                array("Package '%s' was successfully deleted." => 'oldName'),
                array("Package '%s' was successfully changed to '%s'." => array('oldName', 'newName'))
            )
        );
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testUpdateActionPostValidSuccessDelete()
    {
        $postData = array(
            'Deploy' => array(
                'Nonnotified' => '0',
                'Success' => '1',
                'Notified' => '0',
                'Error' => '1',
                'Groups' => '0',
            ),
            'Name' => 'newName',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_path',
                'type' => 'file_type',
            ),
            'PostInstMessage' => '',
        );
        $this->_updateForm->expects($this->once())
                          ->method('setData')
                          ->with($postData);
        $this->_updateForm->expects($this->once())
                          ->method('getData')
                          ->willReturn($postData);
        $this->_updateForm->expects($this->once())
                          ->method('isValid')
                          ->willReturn(true);
        $this->_updateForm->expects($this->never())
                          ->method('render');
        $this->_package->expects($this->exactly(2))
                       ->method('fromName')
                       ->with('oldName')
                       ->will($this->returnValue(true));
        $this->_package->expects($this->once())
                       ->method('build')
                       ->with(true)
                       ->will($this->returnValue(true));
        $this->_package->expects($this->once())
                       ->method('updateComputers')
                       ->with($this->isInstanceOf('Model_Package', false, true, false, true, false));
        $this->_testDeletePackage('/console/package/update/?name=oldName', $postData, 'oldName', true);
    }

    public function testUpdateActionPostValidBuildError()
    {
        $postData = array(
            'Deploy' => array(
                'Nonnotified' => '1',
                'Success' => '0',
                'Notified' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'PostInstMessage' => '',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Deploy' => array(
                'Nonnotified' => '1',
                'Success' => '0',
                'Notified' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'PostInstMessage' => '',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'UserActionRequired' => false,
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
            'FileType' => 'file_type',
        );
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $formData = $postData + array('File' => $fileSpec);
        $this->_updateForm->expects($this->once())
                          ->method('setData')
                          ->with($formData);
        $this->_updateForm->expects($this->once())
                          ->method('getData')
                          ->willReturn($formData);
        $this->_updateForm->expects($this->once())
                          ->method('isValid')
                          ->willReturn(true);
        $this->_updateForm->expects($this->never())
                          ->method('render');
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('oldName')
                       ->will($this->returnValue(true));
        $this->_package->expects($this->never())
                       ->method('updateComputers');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->_testBuildPackage(
            '/console/package/update/?name=oldName',
            $postData,
            $packageData,
            false,
            array(array('Error changing Package \'%s\' to \'%s\':' => array('oldName', 'newName')))
        );
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testUpdateActionPostValidReconstructionError()
    {
        $postData = array('Name' => 'newName');
        $this->_updateForm->expects($this->never())
                          ->method('setData');
        $this->_updateForm->expects($this->never())
                          ->method('getData');
        $this->_updateForm->expects($this->never())
                          ->method('isValid');
        $this->_updateForm->expects($this->never())
                          ->method('render');
        $this->_package->expects($this->once())
                       ->method('fromName')
                       ->with('oldName')
                       ->will($this->returnValue(false));
        $this->_package->expects($this->never())
                       ->method('build');
        $this->_package->expects($this->never())
                       ->method('updateComputers');
        $this->_package->expects($this->never())
                       ->method('delete');
        $this->dispatch('/console/package/update/?name=oldName', 'POST', $postData);
        $this->assertRedirectTo('/console/package/index/');
        $this->assertEquals(
            array(array("Could not retrieve data from package '%s'." => 'oldName')),
            $this->_getControllerPlugin('FlashMessenger')->getCurrentErrorMessages()
        );
    }

    /**
     * Common tests for actions invoking _buildPackage()
     *
     * @param string $url URL to dispatch to
     * @param array $postData POST data
     * @param array $packageData Expected package data
     * @param bool $success Build success to test
     * @param mixed[] $extraMessages Success/error messages appended by tested action
     */
    protected function _testBuildPackage($url, $postData, $packageData, $success, $extraMessages=array())
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $name = $postData['Name'];
        $errors = array(
            array('format1' => array('arg1', 'arg2')),
            array('format2' => array('arg3', 'arg4')),
        );

        $this->_package->expects($this->once())
                       ->method('fromArray')
                       ->with($packageData);
        $this->_package->expects($this->once())
                       ->method('build')
                       ->with(true)
                       ->will($this->returnValue($success));
        $this->_package->expects($this->atLeastOnce())
                       ->method('getErrors')
                       ->will($this->returnValue($errors));

        $this->dispatch($url, 'POST', $postData);
        if ($success) {
            $this->assertEquals(
                array_merge(
                    array(array('Package \'%s\' was successfully created.' => $name)),
                    $extraMessages
                ),
                $flashMessenger->getCurrentSuccessMessages()
            );
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentErrorMessages()
            );
            $this->assertEquals(
                array($name),
                $flashMessenger->getCurrentMessagesFromNamespace('packageName')
            );
        } else {
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentSuccessMessages()
            );
            $this->assertEquals(
                array_merge(
                    array(array('Error creating Package \'%s\':' => $name)),
                    $extraMessages
                ),
                $flashMessenger->getCurrentErrorMessages()
            );
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentMessagesFromNamespace('packageName')
            );
        }
        $this->assertEquals(
            count($extraMessages) == 2 ? array_merge($errors, $errors) : $errors,
            $flashMessenger->getCurrentInfoMessages()
        );
    }

    /**
     * Common tests for actions invoking _deletePackage()
     *
     * @param string $url URL to dispatch to
     * @param array $postData $POST data
     * @param bool $success Deletion success to test
     */
    protected function _testDeletePackage($url, $postData, $name, $success)
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $errors = array(
            array('format1' => array('arg1', 'arg2')),
            array('format2' => array('arg3', 'arg4')),
        );
        $this->_package->expects($this->atLeastOnce())
                       ->method('getErrors')
                       ->will($this->returnValue($errors));
        if ($success) {
            $this->_package->expects($this->once())
                           ->method('delete')
                           ->will($this->returnValue(true));
        } else {
            $this->_package->expects($this->never())
                           ->method('delete');
        }
        $this->dispatch($url, 'POST', $postData);
        if ($success) {
            $this->assertContains(
                array('Package \'%s\' was successfully deleted.' => $name),
                $flashMessenger->getCurrentSuccessMessages()
            );
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentErrorMessages()
            );
        } else {
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentSuccessMessages()
            );
            $this->assertEquals(
                array(array('Package \'%s\' could not be deleted.' => 'Name')),
                $flashMessenger->getCurrentErrorMessages()
            );
        }
        $this->assertEquals(
            count($flashMessenger->getCurrentSuccessMessages()) > 1 ? array_merge($errors, $errors) : $errors,
            $flashMessenger->getCurrentInfoMessages()
        );
    }
}
