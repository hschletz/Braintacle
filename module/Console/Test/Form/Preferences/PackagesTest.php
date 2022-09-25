<?php

/**
 * Tests for Packages form
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Test\Form\Preferences;

use Laminas\Dom\Document\Query;

/**
 * Tests for Packages form
 */
class PackagesTest extends \Console\Test\AbstractFormTest
{
    /**
     * Dummy data for Deploy fieldset
     * @var array
     */
    protected $_deployData = array(
        'defaultDeployPending' => '0',
        'defaultDeployRunning' => '0',
        'defaultDeploySuccess' => '0',
        'defaultDeployError' => '0',
        'defaultDeployGroups' => '0',
    );

    public function testInit()
    {
        $preferences = $this->_form->get('Preferences');

        $deploy = $preferences->get('Deploy');
        $this->assertInstanceOf('Laminas\Form\Fieldset', $deploy);
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deploy->get('defaultDeployPending'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deploy->get('defaultDeployRunning'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deploy->get('defaultDeploySuccess'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deploy->get('defaultDeployError'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $deploy->get('defaultDeployGroups'));

        $this->assertInstanceOf('Laminas\Form\Element\Select', $preferences->get('defaultPlatform'));
        $this->assertInstanceOf('Laminas\Form\Element\Select', $preferences->get('defaultAction'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('defaultActionParam'));
        $this->assertInstanceOf('Library\Form\Element\SelectSimple', $preferences->get('defaultPackagePriority'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $preferences->get('defaultMaxFragmentSize'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $preferences->get('defaultWarn'));
        $this->assertInstanceOf('\Laminas\Form\Element\Textarea', $preferences->get('defaultWarnMessage'));
        $this->assertInstanceOf('\Laminas\Form\Element\Text', $preferences->get('defaultWarnCountdown'));
        $this->assertInstanceOf('\Laminas\Form\Element\Checkbox', $preferences->get('defaultWarnAllowAbort'));
        $this->assertInstanceOf('\Laminas\Form\Element\Checkbox', $preferences->get('defaultWarnAllowDelay'));
        $this->assertInstanceOf('\Laminas\Form\Element\Textarea', $preferences->get('defaultPostInstMessage'));
        $this->assertInstanceOf('Library\Form\Element\Submit', $this->_form->get('Submit'));
    }

    public function testInputFilterValidMinimal()
    {
        $preferences = array(
            'Deploy' => $this->_deployData,
            'defaultPlatform' => 'linux',
            'defaultAction' => 'execute',
            'defaultActionParam' => '',
            'defaultPackagePriority' => '0',
            'defaultMaxFragmentSize' => '',
            'defaultWarn' => '0',
            'defaultWarnMessage' => '',
            'defaultWarnCountdown' => ' ',
            'defaultWarnAllowAbort' => '0',
            'defaultWarnAllowDelay' => '0',
            'defaultPostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertSame('', $preferences['defaultMaxFragmentSize']);
        $this->assertSame('', $preferences['defaultWarnCountdown']);
    }

    public function testInputFilterValidInteger()
    {
        $preferences = array(
            'Deploy' => $this->_deployData,
            'defaultPlatform' => 'linux',
            'defaultAction' => 'execute',
            'defaultActionParam' => 'param',
            'defaultPackagePriority' => '0',
            'defaultMaxFragmentSize' => ' 1.234 ',
            'defaultWarn' => '0',
            'defaultWarnMessage' => '',
            'defaultWarnCountdown' => ' 5.678 ',
            'defaultWarnAllowAbort' => '0',
            'defaultWarnAllowDelay' => '0',
            'defaultPostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertTrue($this->_form->isValid());
        $preferences = $this->_form->getData()['Preferences'];
        $this->assertSame(1234, $preferences['defaultMaxFragmentSize']);
        $this->assertSame(5678, $preferences['defaultWarnCountdown']);
    }

    public function testInputFilterInvalidInteger()
    {
        $preferences = array(
            'Deploy' => $this->_deployData,
            'defaultPlatform' => 'linux',
            'defaultAction' => 'execute',
            'defaultActionParam' => 'param',
            'defaultPackagePriority' => '0',
            'defaultMaxFragmentSize' => '1a',
            'defaultWarn' => '0',
            'defaultWarnMessage' => '',
            'defaultWarnCountdown' => '2a',
            'defaultWarnAllowAbort' => '0',
            'defaultWarnAllowDelay' => '0',
            'defaultPostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertFalse($this->_form->isValid());
        $messages = $this->_form->getMessages()['Preferences'];
        $this->assertCount(2, $messages);
        $this->assertArrayHasKey('defaultMaxFragmentSize', $messages);
        $this->assertArrayHasKey('defaultWarnCountdown', $messages);
    }

    public function testSetDataIntegerValues()
    {
        $preferences = array(
            'Deploy' => $this->_deployData,
            'defaultPlatform' => 'linux',
            'defaultAction' => 'execute',
            'defaultActionParam' => 'param',
            'defaultPackagePriority' => '0',
            'defaultMaxFragmentSize' => '1234',
            'defaultWarn' => '0',
            'defaultWarnMessage' => '',
            'defaultWarnCountdown' => '5678',
            'defaultWarnAllowAbort' => '0',
            'defaultWarnAllowDelay' => '0',
            'defaultPostInstMessage' => '',
            '_csrf' => $this->_form->get('_csrf')->getValue(),
        );
        $this->_form->setValidationGroup(['Preferences']);
        $this->_form->setData(array('Preferences' => $preferences));
        $this->assertSame('1.234', $this->_form->get('Preferences')->get('defaultMaxFragmentSize')->getValue());
        $this->assertSame('5.678', $this->_form->get('Preferences')->get('defaultWarnCountdown')->getValue());
    }

    public function testRenderFieldset()
    {
        $view = $this->createView();
        $html = $this->_form->render($view);
        $document = new \Laminas\Dom\Document($html);

        // Custom rendering of Deploy fieldset - labels are appended instead of prepended
        $this->assertCount(5, Query::execute('//fieldset//input[@type="checkbox"]/following-sibling::span', $document));

        // Assert that other elements are rendered
        $this->assertCount(1, Query::execute('//select[@name="Preferences[defaultAction]"]', $document));
    }
}
