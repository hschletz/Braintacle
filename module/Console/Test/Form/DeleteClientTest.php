<?php

/**
 * Tests for DeleteClient
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\Form;

use Braintacle\Test\DomMatcherTrait;
use Console\Form\DeleteClient;
use Console\Test\AbstractFormTestCase;
use Laminas\Form\Element\Checkbox;
use Model\Config;

/**
 * Tests for DeleteClient
 */
class DeleteClientTest extends AbstractFormTestCase
{
    use DomMatcherTrait;

    private function createConfig(int $defaultDeleteInterfaces): Config
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->once())
            ->method('__get')
            ->with('defaultDeleteInterfaces')
            ->willReturn($defaultDeleteInterfaces);

        return $config;
    }

    private function createForm(int $defaultDeleteInterfaces): DeleteClient
    {
        $config = $this->createConfig($defaultDeleteInterfaces);
        $form = new DeleteClient(null, ['config' => $config]);
        $form->init();

        return $form;
    }

    /** {@inheritdoc} */
    protected function getForm()
    {
        return $this->createForm(0);
    }

    public function testInit()
    {
        $deleteInterfaces = $this->_form->get('DeleteInterfaces');
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deleteInterfaces);
        $yes = $this->_form->get('yes');
        $this->assertInstanceOf('Library\Form\Element\Submit', $yes);
        $no = $this->_form->get('no');
        $this->assertInstanceOf('Library\Form\Element\Submit', $no);
    }

    public function testDeleteInterfacesDefaultChecked()
    {
        $form = $this->createForm(1);
        /** @var Checkbox */
        $deleteInterfaces = $form->get('DeleteInterfaces');
        $this->assertTrue($deleteInterfaces->isChecked());
    }

    public function testDeleteInterfacesDefaultUnchecked()
    {
        $form = $this->createForm(0);
        /** @var Checkbox */
        $deleteInterfaces = $form->get('DeleteInterfaces');
        $this->assertFalse($deleteInterfaces->isChecked());
    }

    public function testRender()
    {
        $output = $this->_form->render($this->createView());
        $xPath = $this->createXpath($output);
        $this->assertXpathCount(1, $xPath, '//input[@type="checkbox"][@name="DeleteInterfaces"]');
        $this->assertXpathCount(1, $xPath, '//input[@type="submit"][@name="yes"]');
        $this->assertXpathCount(1, $xPath, '//input[@type="submit"][@name="no"]');
    }
}
