<?php
/**
 * Tests for DeleteClient
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

namespace Console\Test\Form;

use Zend\Dom\Document\Query as Query;

/**
 * Tests for DeleteClient
 */
class DeleteClientTest extends \Console\Test\AbstractFormTest
{
    /**
     * Config mock
     * @var \Model\Config
     */
    protected $_config;

    public function setUp()
    {
        $this->_config = $this->createMock('Model\Config');
        return parent::setUp();
    }

    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\DeleteClient(null, array('config' => $this->_config));
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $deleteInterfaces = $this->_form->get('DeleteInterfaces');
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $deleteInterfaces);
        $yes = $this->_form->get('yes');
        $this->assertInstanceOf('Library\Form\Element\Submit', $yes);
        $no = $this->_form->get('no');
        $this->assertInstanceOf('Library\Form\Element\Submit', $no);
    }

    public function testDeleteInterfacesDefaultChecked()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('defaultDeleteInterfaces')
                      ->willReturn(1);
        $this->assertTrue($this->_getForm()->get('DeleteInterfaces')->isChecked());
    }

    public function testDeleteInterfacesDefaultUnchecked()
    {
        $this->_config->expects($this->once())
                      ->method('__get')
                      ->with('defaultDeleteInterfaces')
                      ->willReturn(0);
        $this->assertFalse($this->_getForm()->get('DeleteInterfaces')->isChecked());
    }

    public function testRender()
    {
        $output = $this->_form->render($this->_createView());
        $document = new \Zend\Dom\Document($output);
        $this->assertCount(
            1,
            Query::Execute('//input[@type="checkbox"][@name="DeleteInterfaces"]', $document)
        );
        $this->assertCount(
            1,
            Query::Execute('//input[@type="submit"][@name="yes"]', $document)
        );
        $this->assertCount(
            1,
            Query::Execute('//input[@type="submit"][@name="no"]', $document)
        );
    }
}
