<?php

/**
 * Tests for ShowDuplicates form
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

use Console\Form\ShowDuplicates;
use Laminas\Translator\TranslatorInterface;
use Model\Client\DuplicatesManager;
use PHPUnit\Framework\TestCase;

class ShowDuplicatesTest extends TestCase
{
    protected function createForm()
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturnCallback(fn ($message) => sprintf('TRANSLATE(%s)', $message));

        return new ShowDuplicates($translator);
    }

    public function testInit()
    {
        $form = $this->createForm();
        $mergeOptions = $form->get('mergeOptions');
        $this->assertInstanceOf('\Laminas\Form\Element\MultiCheckbox', $mergeOptions);
    }

    public static function initMergeOptionsProvider()
    {
        return [
            ['mergeConfig'],
            ['mergeCustomFields'],
            ['mergeGroups'],
            ['mergePackages'],
            ['mergeProductKey'],
        ];
    }

    /** @dataProvider initMergeOptionsProvider */
    public function testInitMergeOptions($option)
    {
        $expectedOptions = [
            DuplicatesManager::MERGE_CUSTOM_FIELDS,
            DuplicatesManager::MERGE_CONFIG,
            DuplicatesManager::MERGE_GROUPS,
            DuplicatesManager::MERGE_PACKAGES,
            DuplicatesManager::MERGE_PRODUCT_KEY,
        ];

        $form = $this->createForm();
        $form->init();

        $this->assertEquals($expectedOptions, array_keys($form->get('mergeOptions')->getValueOptions()));
    }

    public function testInputFilterClients()
    {
        $form = $this->createForm();

        // Test without "clients" array (happens when no client is selected)
        $data = [
            'submit' => 'Merge selected clients',
        ];
        $form->setData($data);
        $this->assertFalse($form->isValid());

        // Test with empty "clients" array
        $data['clients'] = [];
        $form->setData($data);
        $this->assertFalse($form->isValid());

        // Test with 2 identical clients
        $data['clients'] = ['1', '1'];
        $form->setData($data);
        $this->assertFalse($form->isValid());

        // Test with invalid array content
        $data['clients'] = ['1', 'a'];
        $form->setData($data);
        $this->assertFalse($form->isValid());

        // Test with 2 identical clients + 1 extra
        $data['clients'] = ['1', '1', '2'];
        $form->setData($data);
        $this->assertTrue($form->isValid());

        // Test filtered and validated data
        $this->assertEquals(['1', '2'], array_values($form->getData()['clients']));
    }

    public function testInputFilterClientsNonArray()
    {
        $this->expectException('InvalidArgumentException');
        $data = [
            'submit' => 'Merge selected clients',
            'clients' => '',
        ];
        $form = $this->createForm();
        $form->setData($data);
        $form->isValid();
    }

    public function testInputFilterMergeOptions()
    {
        $form = $this->createForm();

        $data = [
            'submit' => 'Merge selected clients',
            'clients' => [1, 2],
        ];
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $this->assertSame([], $form->getData()['mergeOptions']);

        $data['mergeOptions'] = ['mergeCustomFields', 'mergePackages'];
        $form->setData($data);
        $this->assertTrue($form->isValid());
        $this->assertEquals(['mergeCustomFields', 'mergePackages'], $form->getData()['mergeOptions']);

        $data['mergeOptions'][] = 'invalid';
        $form->setData($data);
        $this->assertFalse($form->isValid());
    }

    public function testGetMessagesError()
    {
        $data = [
            'submit' => 'Merge selected clients',
            'mergeOptions' => 'invalid',
        ];
        $form = $this->createForm();
        $form->setData($data);
        $form->isValid();

        $messages = $form->getMessages();
        $this->assertArrayHasKey('clients', $messages);
        $this->assertArrayHasKey('mergeOptions', $messages);
        $this->assertCount(2, $messages);

        $this->assertEquals(['TRANSLATE(At least 2 different clients have to be selected)'], $messages['clients']);

        $this->assertEquals($messages['clients'], $form->getMessages('clients'));
        $this->assertEquals($messages['mergeOptions'], $form->getMessages('mergeOptions'));
    }

    public function testGetMessagesSuccess()
    {
        $data = [
            'submit' => 'Merge selected clients',
            'clients' => [1, 2],
        ];
        $form = $this->createForm();
        $form->setData($data);
        $form->isValid();

        $this->assertSame([], $form->getMessages());
        $this->assertSame([], $form->getMessages('clients'));
        $this->assertSame([], $form->getMessages('mergeOptions'));
    }
}
