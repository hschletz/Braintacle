<?php

/**
 * Form for display/setting of 'packages' preferences
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

namespace Console\Form\Preferences;

/**
 * Form for display/setting of 'packages' preferences
 */
class Packages extends AbstractForm
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        $preferences = $this->get('Preferences');
        $inputFilter = new \Laminas\InputFilter\InputFilter();
        $integerFilter = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'normalize'),
                'callback_params' => 'integer',
            )
        );
        $integerValidator = array(
            'name' => 'Callback',
            'options' => array(
                'callback' => array($this, 'validateType'),
                'callbackOptions' => 'integer',
            )
        );

        $deploy = new \Laminas\Form\Fieldset('Deploy');
        $deploy->setLabel('Defaults for deploying updated packages');

        $deployPending = new \Laminas\Form\Element\Checkbox('defaultDeployPending');
        $deployPending->setLabel('Pending');
        $deploy->add($deployPending);

        $deployRunning = new \Laminas\Form\Element\Checkbox('defaultDeployRunning');
        $deployRunning->setLabel('Running');
        $deploy->add($deployRunning);

        $deploySuccess = new \Laminas\Form\Element\Checkbox('defaultDeploySuccess');
        $deploySuccess->setLabel('Success');
        $deploy->add($deploySuccess);

        $deployError = new \Laminas\Form\Element\Checkbox('defaultDeployError');
        $deployError->setLabel('Error');
        $deploy->add($deployError);

        $deployGroups = new \Laminas\Form\Element\Checkbox('defaultDeployGroups');
        $deployGroups->setLabel('Groups');
        $deploy->add($deployGroups);

        $preferences->add($deploy);

        $defaultPlatform = new \Laminas\Form\Element\Select('defaultPlatform');
        $defaultPlatform->setLabel('Default platform')
                        ->setAttribute('type', 'select_untranslated')
                        ->setValueOptions(
                            array(
                                'windows' => 'Windows',
                                'linux' => 'Linux',
                                'mac' => 'MacOS'
                            )
                        );
        $preferences->add($defaultPlatform);

        $defaultAction = new \Laminas\Form\Element\Select('defaultAction');
        $defaultAction->setLabel('Default action')
                      ->setValueOptions(
                          array(
                            'launch' => $this->_('Download package, execute command, retrieve result'),
                            'execute' => $this->_('Optionally download package, execute command'),
                            'store' => $this->_('Just download package to target path'),
                          )
                      );
        $preferences->add($defaultAction);

        $defaultActionParam = new \Laminas\Form\Element\Text('defaultActionParam');
        $defaultActionParam->setLabel('Default action parameter');
        $preferences->add($defaultActionParam);

        $defaultPackagePriority = new \Library\Form\Element\SelectSimple('defaultPackagePriority');
        $defaultPackagePriority->setValueOptions(range(0, 10))
                               ->setLabel('Default priority (0: exclusive, 10: lowest)');
        $preferences->add($defaultPackagePriority);

        $defaultMaxFragmentSize = new \Laminas\Form\Element\Text('defaultMaxFragmentSize');
        $defaultMaxFragmentSize->setAttribute('size', '8')
                               ->setLabel('Default maximum fragment size (kB)');
        $preferences->add($defaultMaxFragmentSize);
        $inputFilter->add(
            array(
                'name' => 'defaultMaxFragmentSize',
                'required' => false,
                'filters' => array($integerFilter),
                'validators' => array($integerValidator),
            )
        );

        $defaultWarn = new \Laminas\Form\Element\Checkbox('defaultWarn');
        $defaultWarn->setLabel('Warn user by default');
        $preferences->add($defaultWarn);

        $defaultWarnMessage = new \Laminas\Form\Element\Textarea('defaultWarnMessage');
        $defaultWarnMessage->setLabel('Default warn message');
        $preferences->add($defaultWarnMessage);

        $defaultWarnCountdown = new \Laminas\Form\Element\Text('defaultWarnCountdown');
        $defaultWarnCountdown->setAttribute('size', '5')
                             ->setLabel('Default warn countdown (seconds)');
        $preferences->add($defaultWarnCountdown);
        $inputFilter->add(
            array(
                'name' => 'defaultWarnCountdown',
                'required' => false,
                'filters' => array($integerFilter),
                'validators' => array($integerValidator),
            )
        );

        $defaultWarnAllowAbort = new \Laminas\Form\Element\Checkbox('defaultWarnAllowAbort');
        $defaultWarnAllowAbort->setLabel('Allow user abort by default');
        $preferences->add($defaultWarnAllowAbort);

        $defaultWarnAllowDelay = new \Laminas\Form\Element\Checkbox('defaultWarnAllowDelay');
        $defaultWarnAllowDelay->setLabel('Allow user delay by default');
        $preferences->add($defaultWarnAllowDelay);

        $defaultPostInstMessage = new \Laminas\Form\Element\Textarea('defaultPostInstMessage');
        $defaultPostInstMessage->setLabel('Default post-installation message');
        $preferences->add($defaultPostInstMessage);

        $parentFilter = new \Laminas\InputFilter\InputFilter();
        $parentFilter->add($inputFilter, 'Preferences');
        $this->setInputFilter($parentFilter);
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['Preferences']['defaultMaxFragmentSize'] = $this->localize(
            $data['Preferences']['defaultMaxFragmentSize'],
            'integer'
        );
        $data['Preferences']['defaultWarnCountdown'] = $this->localize(
            $data['Preferences']['defaultWarnCountdown'],
            'integer'
        );
        return parent::setData($data);
    }

    /** {@inheritdoc} */
    public function renderFieldset(\Laminas\View\Renderer\PhpRenderer $view, \Laminas\Form\Fieldset $fieldset)
    {
        $output = '';
        if ($fieldset->getName() == 'Preferences[Deploy]') {
            foreach ($fieldset as $element) {
                // Default renderer would prepend
                $output .= $view->formRow($element, 'append') . "\n";
            }
        } else {
            $output .= parent::renderFieldset($view, $fieldset);
        }
        return $output;
    }
}
