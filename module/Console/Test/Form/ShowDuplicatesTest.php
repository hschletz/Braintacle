<?php
/**
 * Tests for ShowDuplicates form
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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

class ShowDuplicatesTest extends \Console\Test\AbstractFormTest
{
    /** {@inheritdoc} */
    protected function _getForm()
    {
        $form = new \Console\Form\ShowDuplicates(null, ['config' => $this->createMock('Model\Config')]);
        $form->init();
        return $form;
    }

    public function testInit()
    {
        $mergeOptions = $this->_form->get('mergeOptions');
        $this->assertInstanceOf('\Zend\Form\Element\MultiCheckbox', $mergeOptions);
        $this->assertInstanceOf('\Library\Form\Element\Submit', $this->_form->get('submit'));
    }

    public function initMergeOptionsProvider()
    {
        return [
            ['mergeCustomFields'],
            ['mergeGroups'],
            ['mergePackages'],
        ];
    }

    /** @dataProvider initMergeOptionsProvider */
    public function testInitMergeOptions($option)
    {
        $config = $this->createMock('Model\Config');
        $config->method('__get')->willReturnMap([
            ['defaultMergeCustomFields', (integer) ($option == 'mergeCustomFields')],
            ['defaultMergeGroups', (integer) ($option == 'mergeGroups')],
            ['defaultMergePackages', (integer) ($option == 'mergePackages')],
        ]);

        $expectedOptions = [
            [
                'value' => 'mergeCustomFields',
                'label' => 'Merge user supplied information',
                'selected' => (integer) ($option == 'mergeCustomFields'),
            ],
            [
                'value' => 'mergeGroups',
                'label' => 'Merge manual group assignments',
                'selected' => (integer) ($option == 'mergeGroups'),
            ],
            [
                'value' => 'mergePackages',
                'label' => 'Merge missing package assignments',
                'selected' => (integer) ($option == 'mergePackages'),
            ],
        ];

        $form = $this->getMockBuilder($this->_getFormClass())
                     ->setMethods(['getOption'])
                     ->getMock();
        $form->method('getOption')->with('config')->willReturn($config);
        $form->init();

        $this->assertEquals($expectedOptions, $form->get('mergeOptions')->getValueOptions());
    }

    public function testInputFilterClients()
    {
        // Test without "clients" array (happens when no client is selected)
        $data = [
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        ];
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with empty "clients" array
        $data['clients'] = [];
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with 2 identical clients
        $data['clients'] = ['1', '1'];
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with invalid array content
        $data['clients'] = ['1', 'a'];
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());

        // Test with 2 identical clients + 1 extra
        $data['clients'] = ['1', '1', '2'];
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());

        // Test filtered and validated data
        $this->assertEquals(['1', '2'], array_values($this->_form->getData()['clients']));
    }

    public function testInputFilterClientsNonArray()
    {
        $this->expectException('InvalidArgumentException');
        $data = [
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'clients' => '',
        ];
        $this->_form->setData($data);
        $this->_form->isValid();
    }

    public function testInputFilterMergeOptions()
    {
        $data = [
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'clients' => [1, 2],
        ];
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertSame([], $this->_form->getData()['mergeOptions']);

        $data['mergeOptions'] = ['mergeCustomFields', 'mergePackages'];
        $this->_form->setData($data);
        $this->assertTrue($this->_form->isValid());
        $this->assertEquals(['mergeCustomFields', 'mergePackages'], $this->_form->getData()['mergeOptions']);

        $data['mergeOptions'][] = 'invalid';
        $this->_form->setData($data);
        $this->assertFalse($this->_form->isValid());
    }

    public function testGetMessagesError()
    {
        $data = [
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'mergeOptions' => 'invalid',
        ];
        $this->_form->setData($data);
        $this->_form->isValid();

        $messages = $this->_form->getMessages();
        $this->assertCount(2, $messages);
        $this->assertArrayHasKey('clients', $messages);
        $this->assertArrayHasKey('mergeOptions', $messages);

        $this->assertEquals(['TRANSLATE(At least 2 different clients have to be selected)'], $messages['clients']);

        $this->assertEquals($messages['clients'], $this->_form->getMessages('clients'));
        $this->assertEquals($messages['mergeOptions'], $this->_form->getMessages('mergeOptions'));
    }

    public function testGetMessagesSuccess()
    {
        $data = [
            'submit' => 'Merge selected clients',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
            'clients' => [1, 2],
        ];
        $this->_form->setData($data);
        $this->_form->isValid();

        $this->assertSame([], $this->_form->getMessages());
        $this->assertSame([], $this->_form->getMessages('clients'));
        $this->assertSame([], $this->_form->getMessages('mergeOptions'));
    }
}
