<?php
/**
 * Tests for Software form
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

namespace Console\Test\Form;

class SoftwareTest extends \Console\Test\AbstractFormTest
{
    protected $_names = array(
        'name1',
        'name2',
    );

    protected $_namesEncoded = [
        '_bmFtZTE=', // name1
        '_bmFtZTI=', // name2
    ];

    public function testInit()
    {
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Accept'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Ignore'));
    }

    public function testSetDataWithSoftware()
    {
        $data = array(
            'Software' => array(
                'name1' => '1',
                'name2' => '1',
            )
        );
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('createSoftwareFieldset', 'populateValues'))->getMock();
        $form->expects($this->at(0))->method('createSoftwareFieldset')->with($this->_names, true);
        $form->expects($this->at(1))->method('populateValues')->with($data);
        $form->setData($data);
    }

    public function testSetDataNoSoftware()
    {
        $data = array();
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('createSoftwareFieldset', 'populateValues'))->getMock();
        $form->expects($this->at(0))->method('createSoftwareFieldset')->with(array(), true);
        $form->expects($this->at(1))->method('populateValues')->with($data);
        $form->setData($data);
    }

    public function testSetSoftware()
    {
        $software = array(
            array('name' => 'name1'),
            array('name' => 'name2'),
        );
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('createSoftwareFieldset'))->getMock();
        $form->expects($this->once())->method('createSoftwareFieldset')->with($this->_names, false);
        $form->setSoftware($software);
    }

    public function createSoftwareFieldsetProvider()
    {
        return [
            [$this->_namesEncoded, true],
            [$this->_names, false],
        ];
    }

    /**
     * @dataProvider createSoftwareFieldsetProvider
     */
    public function testcreateSoftwareFieldset($names, $namesEncoded)
    {
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('getOption'))->getMock();

        $filter = $this->createMock('Library\Filter\FixEncodingErrors');
        $filter->expects($this->exactly(2))
               ->method('__invoke')
               ->withConsecutive(array('name1'), array('name2'))
               ->willReturnOnConsecutiveCalls('label1', 'label2');
        $form->method('getOption')->with('fixEncodingErrors')->willReturn($filter);

        $form->createSoftwareFieldset($names, $namesEncoded);

        $fieldset = $form->get('Software');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldset);
        $this->assertCount(2, $fieldset);
        foreach (array_values($fieldset->getElements()) as $index => $element) {
            $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $element);
            $this->assertEquals($this->_namesEncoded[$index], $element->getName());
            $this->assertFalse($element->useHiddenElement());
            $this->assertEquals(array('label1', 'label2')[$index], $element->getLabel());
        }
    }

    public function testcreateSoftwareFieldsetRecreateFieldset()
    {
        $form = $this->getMockBuilder($this->_getFormClass())->setMethods(array('getOption'))->getMock();

        $filter = $this->createMock('Library\Filter\FixEncodingErrors');
        $filter->method('__invoke')->willReturn('label');
        $form->method('getOption')->with('fixEncodingErrors')->willReturn($filter);

        $oldFieldset = new \Laminas\Form\Fieldset('Software');
        $oldFieldset->add(new \Laminas\Form\Element\Checkbox('name3'));
        $form->add($oldFieldset);

        $form->createSoftwareFieldset($this->_names, false);

        $fieldset = $form->get('Software');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldset);
        $this->assertCount(2, $fieldset);
        $this->assertEquals($this->_namesEncoded, array_keys($fieldset->getElements()));
    }

    public function testcreateSoftwareFieldsetFilterNotSet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('FixEncodingErrors filter not set');
        $form = $this->_getForm();
        $form->createSoftwareFieldset($this->_names, false);
    }
}
