<?php

/**
 * Tests for PackageController
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

use Braintacle\FlashMessages;
use Braintacle\Legacy\Plugin\FlashMessengerTestTrait;
use Console\Test\AbstractControllerTestCase;
use Model\Package\PackageManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PackageController
 */
class PackageControllerTest extends AbstractControllerTestCase
{
    use FlashMessengerTestTrait;

    /**
     * @var MockObject|PackageManager
     */
    protected $_packageManager;

    /**
     * Set up mock objects
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_packageManager = $this->createMock('Model\Package\PackageManager');

        $serviceManager = $this->getApplicationServiceLocator();
        $serviceManager->setService('Model\Package\PackageManager', $this->_packageManager);
    }

    public function testDeleteActionGet()
    {
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->dispatch('/console/package/delete/?name=Name');
        $this->assertResponseStatusCode(200);
        $this->assertStringContainsString("'Name'", $this->getResponse()->getContent());
    }

    public function testDeleteActionPostNo()
    {
        $this->_packageManager->expects($this->never())->method('deletePackage');
        $this->dispatch('/console/package/delete/?name=Name', 'POST', array('no' => 'No'));
        $this->assertRedirectTo('packagesList/');
    }

    public function testDeleteActionPostYesSuccess()
    {
        $this->initFlashMessages();

        $this->_packageManager->expects($this->once())->method('deletePackage')->with('Name');

        $this->dispatch('/console/package/delete/?name=Name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('packagesList/');

        $this->assertEquals(
            [FlashMessages::Success => ["Paket 'Name' wurde erfolgreich gelöscht."]],
            $this->flashMessages,
        );
    }

    public function testDeleteActionPostYesError()
    {
        $this->initFlashMessages();

        $this->_packageManager->expects($this->once())
            ->method('deletePackage')
            ->with('Name')
            ->will($this->throwException(new \Model\Package\RuntimeException('delete error')));

        $this->dispatch('/console/package/delete/?name=Name', 'POST', array('yes' => 'Yes'));
        $this->assertRedirectTo('packagesList/');

        $this->assertEquals([FlashMessages::Error => ['delete error']], $this->flashMessages);
    }
}
