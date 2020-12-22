<?php
/**
 * Tests for SoftwareController
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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
 * Tests for SoftwareController
 */
class SoftwareControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * Software manager mock
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Filter form mock
     * @var \Console\Form\SoftwareFilter
     */
    protected $_filterForm;

    /**
     * Software form mock
     * @var \Console\Form\Software
     */
    protected $_softwareForm;

    /**
     * Sample result data
     * @var array[]
     */
    protected $_result = array(
        array('name' => 'name', 'num_clients' => 1),
        array('name' => "<name\xC2\x96>", 'num_clients' => 2), // Check for proper encoding and escaping
    );

    /**
     * Session container
     * @var \Zend\Session\Container;
     */
    protected $_session;

    public function setUp(): void
    {
        parent::setUp();

        $this->_session = new \Zend\Session\Container('ManageSoftware');

        $this->_softwareManager = $this->createMock('Model\SoftwareManager');
        $this->_filterForm = $this->createMock('Console\Form\SoftwareFilter');
        $this->_softwareForm = $this->createMock('Console\Form\Software');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\SoftwareManager', $this->_softwareManager);

        $formManager = $serviceManager->get('FormElementManager');
        $formManager->setService('Console\Form\SoftwareFilter', $this->_filterForm);
        $formManager->setService('Console\Form\Software', $this->_softwareForm);
    }

    public function testIndexAction()
    {
        $serviceManager = $this->getApplicationServiceLocator();

        $params = $this->createMock('Zend\Mvc\Controller\Plugin\Params');
        $params->method('__invoke')->willReturnSelf();
        $params->method('fromQuery')->willReturnMap(
            array(
                array('filter', 'accepted', 'filterName'),
                array('os', 'windows', 'osName'),
            )
        );

        $getOrder = $this->createMock('Console\Mvc\Controller\Plugin\GetOrder');
        $getOrder->method('__invoke')->with('name')->willReturn(array('order' => '_order', 'direction' => '_direction'));

        $controllerPluginManager = $serviceManager->get('ControllerPluginManager');
        $controllerPluginManager->setService('params', $params);
        $controllerPluginManager->setService('getOrder', $getOrder);

        $filters = array(
            'Os' => 'osName',
            'Status' => 'filterName',
        );

        $result = $this->createMock('Zend\Db\ResultSet\ResultSet');
        $result->method('toArray')->willReturn($this->_result);

        $this->_softwareManager->method('getSoftware')->with($filters, '_order', '_direction')->willReturn($result);

        $consoleUrl = $this->createMock('Console\View\Helper\ConsoleUrl');
        $consoleUrl->method('__invoke')->with('software', 'confirm')->willReturn('/console/software/confirm');

        $viewHelperManager = $serviceManager->get('ViewHelperManager');
        $viewHelperManager->setService('consoleUrl', $consoleUrl);

        $view = $serviceManager->get('ViewRenderer');

        $this->_filterForm->expects($this->once())->method('setFilter')->with('filterName');
        $this->_filterForm->expects($this->once())->method('render')->with($view)->willReturn("FILTER_FORM\n");

        $this->_softwareForm->expects($this->once())->method('setSoftware')->with($this->_result);
        $this->_softwareForm->expects($this->exactly(2))->method('setAttribute')->withConsecutive(
            array('action', '/console/software/confirm'),
            array('method', 'POST')
        );

        $softwareFormHelper = $this->createMock('Console\View\Helper\Form\Software');
        $softwareFormHelper->method('__invoke')->with(
            $this->_softwareForm,
            $this->_result,
            ['order' => '_order', 'direction' => '_direction'],
            'filterName'
        )->willReturn("SOFTWARE_FORM\n");

        $serviceManager->get('ViewHelperManager')->setService('consoleFormSoftware', $softwareFormHelper);

        $this->dispatch('/console/software/index/');

        $this->assertEquals('filterName', $this->_session->filter);
        $this->assertResponseStatusCode(200);
        $this->assertQueryContentRegex(
            'div',
            "/FILTER_FORM\nSOFTWARE_FORM\n/"
        );
    }

    public function confirmActionButtonProvider()
    {
        return array(
            array('Accept'),
            array('Ignore'),
        );
    }

    /**
     * @dataProvider confirmActionButtonProvider
     */
    public function testConfirmActionPostConfirmInvalid($button)
    {
        $postData = array($button => 'button label');

        $this->_softwareForm->expects($this->once())->method('setData')->with($postData);
        $this->_softwareForm->method('isValid')->willReturn(false);

        $this->_session['filter'] = 'filterName';

        $this->dispatch('/console/software/confirm/', 'POST', $postData);

        $this->assertRedirectTo('/console/software/index/?filter=filterName');
        $this->assertEquals(array('filter' => 'filterName'), $this->_session->getArrayCopy());
    }

    /**
     * @dataProvider confirmActionButtonProvider
     */
    public function testConfirmActionPostConfirmValidEmptyList($button)
    {
        $postData = array($button => 'button label');
        $formData = array('Software' => array());

        $this->_softwareForm->expects($this->once())->method('setData')->with($postData);
        $this->_softwareForm->method('isValid')->willReturn(true);
        $this->_softwareForm->expects($this->once())->method('getData')->willReturn($formData);

        $this->_session['filter'] = 'filterName';

        $this->dispatch('/console/software/confirm/', 'POST', $postData);

        $this->assertRedirectTo('/console/software/index/?filter=filterName');
        $this->assertEquals(array('filter' => 'filterName'), $this->_session->getArrayCopy());
    }

    public function confirmActionPostConfirmValidNonEmptyListProvider()
    {
        return array(
            array('Accept', true, 'The following software will be marked as known and accepted. Continue?'),
            array('Ignore', false, 'The following software will be marked as known but ignored. Continue?'),
        );
    }

    /**
     * @dataProvider confirmActionPostConfirmValidNonEmptyListProvider
     */
    public function testConfirmActionPostConfirmValidNonEmptyList($button, $accept, $message)
    {
        $postData = array($button => 'button label');
        $formData = array(
            'Software' => array(
                'cmF3MQ==' => '1', // 'raw1'
                'cmF3Mg==' => '1', // 'raw2'
            )
        );

        $this->_softwareForm->expects($this->once())->method('setData')->with($postData);
        $this->_softwareForm->method('isValid')->willReturn(true);
        $this->_softwareForm->expects($this->once())->method('getData')->willReturn($formData);

        $this->_session['filter'] = 'filterName';

        $serviceManager = $this->getApplicationServiceLocator();

        $fixEncodingErrors = $this->createMock('Library\Filter\FixEncodingErrors');
        $fixEncodingErrors->method('filter')
                          ->withConsecutive(array('raw1'), array('raw2'))
                          ->willReturnOnConsecutiveCalls('filtered1', 'filtered2');
        $serviceManager->get('FilterManager')->setService('Library\Filter\FixEncodingErrors', $fixEncodingErrors);

        $viewHelperManager = $serviceManager->get('ViewHelperManager');

        $translate = $this->createMock('Zend\I18n\View\Helper\Translate');
        $translate->method('__invoke')->with($message)->willReturn('MESSAGE');
        $viewHelperManager->setService('Zend\I18n\View\Helper\Translate', $translate);

        $consoleUrl = $this->createMock('Console\View\Helper\ConsoleUrl');
        $consoleUrl->method('__invoke')->with('software', 'manage')->willReturn('URL');
        $viewHelperManager->setService('consoleUrl', $consoleUrl);

        $formYesNo = $this->createMock('Library\View\Helper\FormYesNo');
        $formYesNo->method('__invoke')->with('MESSAGE', array(), array('action' => 'URL'))->willReturn('<form>FORM</form>');
        $viewHelperManager->setService('Library\View\Helper\FormYesNo', $formYesNo);

        $htmlList = $this->createMock('Zend\View\Helper\HtmlList');
        $htmlList->method('__invoke')->with(array('filtered1', 'filtered2'))->willReturn('LIST');
        $viewHelperManager->setService('Zend\View\Helper\HtmlList', $htmlList);

        $this->dispatch('/console/software/confirm/', 'POST', $postData);

        $this->assertResponseStatusCode(200);
        $this->assertQueryContentContains('//form', 'FORM');
        $this->assertQueryContentContains('//div[@class="textcenter"]', "\nLIST\n");

        $this->assertEquals(
            1,
            $this->_session->getManager()->getStorage()->getMetadata('ManageSoftware')['EXPIRE_HOPS']['hops']
        );
        $this->assertEquals(
            array('raw1', 'raw2'),
            $this->_session['software']
        );
        $this->assertSame($accept, $this->_session['display']);
    }

    public function testConfirmActionGet()
    {
        $this->dispatch('/console/software/confirm/');
        $this->assertResponseStatusCode(400);
        $this->assertEmpty($this->_session->getArrayCopy());
    }

    public function testConfirmActionPostBadRequest()
    {
        $this->dispatch('/console/software/confirm/', 'POST', array());
        $this->assertResponseStatusCode(400);
        $this->assertEmpty($this->_session->getArrayCopy());
    }

    public function testManageActionPostNo()
    {
        $this->_softwareManager->expects($this->never())->method('setDisplay');
        $this->_session['filter'] = 'FILTER';

        $this->dispatch('/console/software/manage/', 'POST', array('no' => 'No'));

        $this->assertRedirectTo('/console/software/index/?filter=FILTER');
    }

    public function testManageActionPostYesEmptyList()
    {
        $this->_softwareManager->expects($this->never())->method('setDisplay');

        $this->_session['filter'] = 'FILTER';
        $this->_session['software'] = array();

        $this->dispatch('/console/software/manage/', 'POST', array('yes' => 'Yes'));

        $this->assertRedirectTo('/console/software/index/?filter=FILTER');
    }

    public function testManageActionPostYesNonEmptyList()
    {
        $this->_softwareManager->expects($this->exactly(2))
                               ->method('setDisplay')
                               ->withConsecutive(array('name1'), array('name2'));

        $this->_session['filter'] = 'FILTER';
        $this->_session['software'] = array('name1', 'name2');
        $this->_session['display'] = 'DISPLAY';

        $this->dispatch('/console/software/manage/', 'POST', array('yes' => 'Yes'));

        $this->assertRedirectTo('/console/software/index/?filter=FILTER');
    }

    public function testManageActionGet()
    {
        $this->_softwareManager->expects($this->never())->method('setDisplay');

        $this->dispatch('/console/software/manage/');
        $this->assertResponseStatusCode(400);
    }

    public function testManageActionPostBadRequest()
    {
        $this->_softwareManager->expects($this->never())->method('setDisplay');

        $this->dispatch('/console/software/manage/', 'POST', array());
        $this->assertResponseStatusCode(400);
    }
}
