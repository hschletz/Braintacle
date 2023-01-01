<?php

/**
 * Tests for SoftwareController
 *
 * Copyright (C) 2011-2023 Holger Schletz <holger.schletz@web.de>
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

use Console\Form\SoftwareManagementForm;
use Console\Mvc\Controller\Plugin\GetOrder;
use Laminas\Session\Container;
use Model\SoftwareManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for SoftwareController
 */
class SoftwareControllerTest extends \Console\Test\AbstractControllerTest
{
    /**
     * @var MockObject|SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Sample result data
     * @var array[]
     */
    protected $_result = [
        ['name' => 'name', 'num_clients' => 1],
        ['name' => "<name>", 'num_clients' => 2], // Check for proper escaping
    ];

    /**
     * Session container
     * @var \Laminas\Session\Container;
     */
    protected $_session;

    public function setUp(): void
    {
        parent::setUp();

        $this->_session = new \Laminas\Session\Container('ManageSoftware');

        $this->_softwareManager = $this->createMock('Model\SoftwareManager');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\SoftwareManager', $this->_softwareManager);
    }

    public function testIndexActionParameterEvaluation()
    {
        $serviceManager = $this->getApplicationServiceLocator();

        $params = $this->createMock('Laminas\Mvc\Controller\Plugin\Params');
        $params->method('__invoke')->willReturnSelf();
        $params->method('fromQuery')->willReturnMap(
            array(
                array('filter', 'accepted', 'filterName'),
                array('os', 'windows', 'osName'),
            )
        );

        $getOrder = $this->createMock(GetOrder::class);
        $getOrder->method('__invoke')->with('name')->willReturn(array('order' => '_order', 'direction' => '_direction'));

        $controllerPluginManager = $serviceManager->get('ControllerPluginManager');
        $controllerPluginManager->setService('params', $params);
        $controllerPluginManager->setService('getOrder', $getOrder);

        $filters = array(
            'Os' => 'osName',
            'Status' => 'filterName',
        );

        $this->_softwareManager->method('getSoftware')
             ->with($filters, '_order', '_direction')
             ->willReturn($this->_result);

        $this->dispatch('/console/software/index/');

        $this->assertEquals('filterName', $this->_session->filter);
        $this->assertResponseStatusCode(200);
    }

    public function indexActionFilterProvider()
    {
        return [
            ['accepted'],
            ['ignored'],
            ['new'],
            ['all'],
        ];
    }

    /**
     * @dataProvider indexActionFilterProvider
     */
    public function testIndexActionFilterSelect(string $filter)
    {
        $this->dispatch('/console/software/index/?filter=' . $filter);
        $this->assertXpathQuery("//option[@value='$filter'][@selected]");
        $this->assertXpathQueryCount('//option[@selected]', 1);
    }

    public function testIndexActionInvalidFilter()
    {
        $this->dispatch('/console/software/index/?filter=invalid');
        $this->assertNotXpathQuery('//option[@selected]');
    }

    public function testIndexActioneFilterButtonsAccepted()
    {
        $this->dispatch('/console/software/index/?filter=accepted');
        $this->assertNotXpathQuery('//button[@name="accept"]');
        $this->assertXpathQuery('//button[@name="ignore"]');
    }

    public function testIndexActioneFilterButtonsIgnored()
    {
        $this->dispatch('/console/software/index/?filter=ignored');
        $this->assertXpathQuery('//button[@name="accept"]');
        $this->assertNotXpathQuery('//button[@name="ignore"]');
    }

    public function testIndexActioneFilterButtonsNew()
    {
        $this->dispatch('/console/software/index/?filter=new');
        $this->assertXpathQuery('//button[@name="accept"]');
        $this->assertXpathQuery('//button[@name="ignore"]');
    }

    public function testIndexActioneFilterButtonsAll()
    {
        $this->dispatch('/console/software/index/?filter=new');
        $this->assertXpathQuery('//button[@name="accept"]');
        $this->assertXpathQuery('//button[@name="ignore"]');
    }

    public function testIndexActionSoftwareList()
    {
        $this->_softwareManager->method('getSoftware')->willReturn($this->_result);
        $this->dispatch('/console/software/index/');
        $this->assertXpathQueryCount('//tr[td]', 2);

        $this->assertXpathQuery('//tr[2]/td[1]/input[@value="name"]');
        $this->assertXpathQuery('//tr[2]/td[2][text()="name"]');
        $this->assertXpathQuery('//tr[2]/td[3]/a[normalize-space(text())="1"][contains(@href, "search=name")]');

        $this->assertXpathQuery('//tr[3]/td[1]/input[@value="<name>"]');
        $this->assertXpathQuery('//tr[3]/td[2][text()="<name>"]');
        $this->assertXpathQuery('//tr[3]/td[3]/a[normalize-space(text())="2"][contains(@href, "search=%3Cname%3E")]');
    }

    public function confirmActionValidProvider()
    {
        return [
            ['accept', true, 'Die folgende Software wird als bekannt und akzeptiert markiert. Fortfahren?'],
            ['ignore', false, 'Die folgende Software wird als bekannt aber ignoriert markiert. Fortfahren?'],
        ];
    }

    /**
     * @dataProvider confirmActionValidProvider
     */
    public function testConfirmActionValid(string $button, bool $display, string $message)
    {
        $software = ['software1', 'software2'];
        $postData = [
            'software' => $software,
            $button => '',
        ];

        /** @var MockObject|SoftwareManagementForm */
        $softwareManagementForm = $this->createMock(SoftwareManagementForm::class);
        $softwareManagementForm->method('getValidationMessages')->with($postData)->willReturn([]);
        $this->getApplicationServiceLocator()->setService(SoftwareManagementForm::class, $softwareManagementForm);

        $this->dispatch('/console/software/confirm/', 'POST', $postData);

        $session = new Container('ManageSoftware');
        $this->assertEquals(
            1,
            $session->getManager()->getStorage()->getMetadata('ManageSoftware')['EXPIRE_HOPS']['hops']
        );
        $this->assertEquals($software, $session['software']);
        $this->assertSame($display, $session['display']);

        $this->assertXpathQuery("//p[normalize-space(text())='$message']");
        $this->assertXpathQueryCount('//li', 2);
        $this->assertXpathQueryContentContains('//li[1]', 'software1');
        $this->assertXpathQueryContentContains('//li[2]', 'software2');
    }

    public function testConfirmActionInvalid()
    {
        /** @var MockObject|SoftwareManagementForm */
        $softwareManagementForm = $this->createMock(SoftwareManagementForm::class);
        $softwareManagementForm->method('getValidationMessages')->with([])->willReturn(['message']);
        $this->getApplicationServiceLocator()->setService(SoftwareManagementForm::class, $softwareManagementForm);

        $session = new Container('ManageSoftware');
        $session['filter'] = 'filterName';

        $this->dispatch('/console/software/confirm/');

        $this->assertRedirectTo('/console/software/index/?filter=filterName');
        $this->assertEquals(['filter' => 'filterName'], $session->getArrayCopy());
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
