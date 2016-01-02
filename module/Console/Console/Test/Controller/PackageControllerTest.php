<?php
/**
 * Tests for PackageController
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

namespace Console\Test\Controller;

/**
 * Tests for PackageController
 */
class PackageControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Package manager mock
     * @var \Model\Package\PackageManager
     */
    protected $_packageManager;

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
        $this->_packageManager = $this->getMockBuilder('Model\Package\PackageManager')
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $this->_config = $this->getMockBuilder('Model\Config')->disableOriginalConstructor()->getMock();
        $this->_buildForm = $this->getMock('Console\Form\Package\Build');
        $this->_updateForm = $this->getMock('Console\Form\Package\Update');
        parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _createController()
    {
        return new \Console\Controller\PackageController(
            $this->_packageManager,
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
        $timestamp1 = new \DateTime('2014-03-29 20:03:45');
        $timestamp2 = new \DateTime('2014-03-29 20:15:43');
        $packages = array(
            array(
                'Name' => 'name1',
                'Comment' => 'comment1',
                'Timestamp' => $timestamp1,
                'Size' => 12345678,
                'Platform' => 'platform',
                'NumPending' => 1,
                'NumRunning' => 2,
                'NumSuccess' => 3,
                'NumError' => 4,
            ),
            array(
                'Name' => 'name2',
                'Comment' => '',
                'Timestamp' => $timestamp2,
                'Size' => 87654321,
                'Platform' => 'platform',
                'NumPending' => 0,
                'NumRunning' => 0,
                'NumSuccess' => 0,
                'NumError' => 0,
            ),
        );
        $this->_packageManager->expects($this->once())->method('getPackages')->willReturn($packages);

        $dateFormat = $this->getMock('Zend\I18n\View\Helper\DateFormat');
        $dateFormat->expects($this->exactly(2))
                   ->method('__invoke')
                   ->withConsecutive(
                       array($timestamp1, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT),
                       array($timestamp2, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT)
                   )
                   ->will($this->onConsecutiveCalls('date1', 'date2'));
        $this->getApplicationServiceLocator()->get('ViewHelperManager')->setService('DateFormat', $dateFormat);

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
            "\ndate1\n"
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
        $query = '//td[@class="textright"]/a[@href="/console/client/index/' .
                 '?columns=Name,UserName,LastContactDate,InventoryDate&jumpto=software&filter=%s&search=%s' .
                 '"][@class="%s"]';

        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackagePending', 'name1', 'package_pending'),
            '1'
        );
        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageRunning', 'name1', 'package_running'),
            '2'
        );
        $this->assertXpathQueryContentContains(
            sprintf($query, 'PackageSuccess', 'name1', 'package_success'),
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
            'Löschen'
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
        $flashMessenger->addMessage('<br>', 'packageName');

        $this->_packageManager->expects($this->once())->method('getPackages')->willReturn(array());
        $this->_disableTranslator();
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
    }

    public function testIndexActionPackageHighlightCurrentPackage()
    {
        $packages = array(
            array(
                'Name' => 'name1',
                'Comment' => 'comment1',
                'Timestamp' => new \DateTime('2014-03-29 20:03:45'),
                'Size' => 12345678,
                'Platform' => 'platform',
                'NumPending' => 1,
                'NumRunning' => 2,
                'NumSuccess' => 3,
                'NumError' => 4,
            ),
            array(
                'Name' => 'name2',
                'Comment' => '',
                'Timestamp' => new \DateTime('2014-03-29 20:15:43'),
                'Size' => 87654321,
                'Platform' => 'platform',
                'NumPending' => 0,
                'NumRunning' => 0,
                'NumSuccess' => 0,
                'NumError' => 0,
            ),
        );
        $this->_getControllerPlugin('FlashMessenger')->addMessage('name1', 'packageName');
        $this->_packageManager->expects($this->once())->method('getPackages')->willReturn($packages);
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
        $this->_packageManager->expects($this->never())->method('buildPackage');
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
        $this->_packageManager->expects($this->never())->method('buildPackage');
        $this->dispatch('/console/package/build', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testBuildActionPostValidSuccess()
    {
        $postData = array(
            'Name' => 'packageName',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Name' => 'packageName',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
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

    public function testDeleteActionGet()
    {
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->dispatch('/console/package/delete/?name=Name');
        $this->assertResponseStatusCode(200);
        $this->assertContains("'Name'", $this->getResponse()->getContent());
    }

    public function testDeleteActionPostNo()
    {
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->dispatch('/console/package/delete/?name=Name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $this->_testDeletePackage('/console/package/delete/?name=Name', array('yes' => 'Yes'), 'Name', true);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testDeleteActionPostYesError()
    {
        $this->_testDeletePackage('/console/package/delete/?name=Name', array('yes' => 'Yes'), 'Name', false);
        $this->assertRedirectTo('/console/package/index/');
    }

    public function testUpdateActionGet()
    {
        $packageData = array(
            'Name' => 'Name',
            'Comment' => 'Comment',
            'Platform' => 'Platform',
            'DeployAction' => 'DeployAction',
            'ActionParam' => 'ActionParam',
            'Priority' => 'Priority',
            'Warn' => 'Warn',
            'WarnMessage' => 'WarnMessage',
            'WarnCountdown' => 'WarnCountdown',
            'WarnAllowAbort' => 'WarnAllowAbort',
            'WarnAllowDelay' => 'WarnAllowDelay',
            'PostInstMessage' => 'PostInstMessage',
        );
        $formData = array(
            'Deploy' => array(
                'Pending' => 'defaultDeployPending',
                'Running' => 'defaultDeployRunning',
                'Success' => 'defaultDeploySuccess',
                'Error' => 'defaultDeployError',
                'Groups' => 'defaultDeployGroups',
            ),
            'MaxFragmentSize' => 'defaultMaxFragmentSize',
        );
        $formData += $packageData;

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
        $this->_packageManager->expects($this->once())
                              ->method('getPackage')
                              ->with('oldName')
                              ->willReturn($packageData);
        $this->_packageManager->expects($this->never())->method('buildPackage');
        $this->_packageManager->expects($this->never())->method('updateAssignments');
        $this->_packageManager->expects($this->never())->method('deletePackage');
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
        $this->_packageManager->expects($this->never())->method('buildPackage');
        $this->_packageManager->expects($this->never())->method('updateAssignments');
        $this->_packageManager->expects($this->never())->method('deletePackage');

        $this->dispatch('/console/package/update/?name=oldName', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $this->assertXpathQuery('//form');
    }

    public function testUpdateActionPostValidBuildSuccess()
    {
        $postData = array(
            'Deploy' => array(
                'Pending' => '1',
                'Running' => '0',
                'Success' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Deploy' => array(
                'Pending' => '1',
                'Running' => '0',
                'Success' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
        );
        $this->getRequest()->getFiles()->set('File', $fileSpec);
        $formData = $postData + array('File' => $fileSpec);
        $oldPackage = array('Id' => '1', 'Name' => 'oldName');

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
        $this->_packageManager->expects($this->exactly(2))
                              ->method('getPackage')
                              ->withConsecutive(array('oldName'), array('newName'))
                              ->will(
                                  $this->onConsecutiveCalls(
                                      $oldPackage,
                                      array('Id' => '2')
                                  )
                              );
        $this->_packageManager->expects($this->once())
                              ->method('buildPackage')
                              ->with($packageData, true);
        $this->_packageManager->expects($this->once())
                              ->method('updateAssignments')
                              ->with('1', '2', '1', '0', '1', '0', '1');
        $this->_packageManager->expects($this->once())->method('deletePackage');

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
                'Pending' => '0',
                'Running' => '1',
                'Success' => '0',
                'Error' => '1',
                'Groups' => '0',
            ),
            'Name' => 'newName',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Deploy' => array(
                'Pending' => '0',
                'Running' => '1',
                'Success' => '0',
                'Error' => '1',
                'Groups' => '0',
            ),
            'Name' => 'newName',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
        );
        $oldPackage = array('Id' => '1', 'Name' => 'oldName');
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
        $this->_packageManager->expects($this->exactly(2))
                              ->method('getPackage')
                              ->withConsecutive(array('oldName'), array('newName'))
                              ->will(
                                  $this->onConsecutiveCalls(
                                      $oldPackage,
                                      array('Id' => '2')
                                  )
                              );
        $this->_packageManager->expects($this->once())
                              ->method('buildPackage')
                              ->with($packageData, true);
        $this->_packageManager->expects($this->once())
                              ->method('updateAssignments')
                              ->with('1', '2', '0', '1', '0', '1', '0');
        $this->_testDeletePackage('/console/package/update/?name=oldName', $postData, 'oldName', true);
    }

    public function testUpdateActionPostValidBuildError()
    {
        $postData = array(
            'Deploy' => array(
                'Pending' => '1',
                'Running' => '0',
                'Success' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
        );
        $fileSpec = array(
            'name' => 'file_name',
            'tmp_name' => 'file_tmp_name',
            'type' => 'file_type',
        );
        $packageData = array(
            'Deploy' => array(
                'Pending' => '1',
                'Running' => '0',
                'Success' => '1',
                'Error' => '0',
                'Groups' => '1',
            ),
            'Name' => 'newName',
            'File' => array(
                'name' => 'file_name',
                'tmp_name' => 'file_tmp_name',
                'type' => 'file_type',
            ),
            'FileName' => 'file_name',
            'FileLocation' => 'file_tmp_name',
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
        $this->_packageManager->expects($this->once())->method('getPackage')->with('oldName');
        $this->_packageManager->expects($this->never())->method('updateAssignments');
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->_testBuildPackage(
            '/console/package/update/?name=oldName',
            $postData,
            $packageData,
            false,
            array("Error changing Package 'oldName' to 'newName'")
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
        $this->_packageManager->expects($this->once())
                              ->method('getPackage')
                              ->with('oldName')
                              ->will($this->throwException(new \Model\Package\RuntimeException('getPackage() error')));
        $this->_packageManager->expects($this->never())->method('buildPackage');
        $this->_packageManager->expects($this->never())->method('updateAssignments');
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->dispatch('/console/package/update/?name=oldName', 'POST', $postData);
        $this->assertRedirectTo('/console/package/index/');
        $this->assertEquals(
            array('getPackage() error'),
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
    protected function _testBuildPackage($url, $postData, $packageData, $success, $extraMessages = array())
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $name = $postData['Name'];
        $errors = array(
            array('format1' => array('arg1', 'arg2')),
            array('format2' => array('arg3', 'arg4')),
        );

        if ($success) {
            $this->_packageManager->expects($this->once())->method('buildPackage')->with($packageData, true);
        } else {
            $this->_packageManager->expects($this->once())
                                  ->method('buildPackage')
                                  ->with($packageData, true)
                                  ->will($this->throwException(new \Model\Package\RuntimeException('build error')));
        }

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
                    array('build error'),
                    $extraMessages
                ),
                $flashMessenger->getCurrentErrorMessages()
            );
            $this->assertEquals(
                array(),
                $flashMessenger->getCurrentMessagesFromNamespace('packageName')
            );
        }
    }

    /**
     * Common tests for actions invoking _deletePackage()
     *
     * @param string $url URL to dispatch to
     * @param array $postData $POST data
     * @param string $name Package name
     * @param bool $success Deletion success to test
     */
    protected function _testDeletePackage($url, $postData, $name, $success)
    {
        $flashMessenger = $this->_getControllerPlugin('FlashMessenger');
        $errors = array(
            array('format1' => array('arg1', 'arg2')),
            array('format2' => array('arg3', 'arg4')),
        );
        if ($success) {
            $this->_packageManager->expects($this->once())->method('deletePackage')->with($name);
        } else {
            $this->_packageManager->expects($this->once())
                                  ->method('deletePackage')
                                  ->with($name)
                                  ->will($this->throwException(new \Model\Package\RuntimeException('delete error')));
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
                array('delete error'),
                $flashMessenger->getCurrentErrorMessages()
            );
        }
    }
}
