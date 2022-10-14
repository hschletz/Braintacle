<?php

/**
 * Form for creating a package
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

namespace Console\Form\Package;

use Laminas\Filter\ToNull;
use Laminas\Form\Element;

/**
 * Form for creating a package
 *
 * The provided fields match the package property names. The packageManager
 * option must be set to a \Model\Package\PackageManager instance before init()
 * is called. The factory does this automatically.
 */
class Build extends \Console\Form\Form
{
    /** {@inheritdoc} */
    public function init()
    {
        parent::init();

        // Add generic class for both Build/Update form.
        $this->setAttribute('class', $this->getAttribute('class') . ' form_package');

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

        // Package name
        $name = new Element\Text('Name');
        $name->setLabel('Name');
        $name->setAttribute('autofocus', true);
        $this->add($name);
        $inputFilter->add(
            array(
                'name' => 'Name',
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'StringLength',
                        'options' => array('max' => 255),
                    ),
                    array(
                        'name' => 'Library\Validator\NotInArray',
                        'options' => array(
                            'haystack' => $this->getOption('packageManager')->getAllNames(),
                            'caseSensitivity' => \Library\Validator\NotInArray::CASE_INSENSITIVE,
                        ),
                    ),
                ),
            )
        );

        // Comment
        $comment = new Element\Textarea('Comment');
        $comment->setLabel('Comment');
        $this->add($comment);
        $inputFilter->add(
            array(
                'name' => 'Comment',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
            )
        );

        // Platform combobox
        $platform = new Element\Select('Platform');
        $platform->setLabel('Platform')
                 ->setAttribute('type', 'select_untranslated')
                 ->setValueOptions(
                     array(
                        'windows' => 'Windows',
                        'linux' => 'Linux',
                        'mac' => 'MacOS'
                     )
                 );
        $this->add($platform);

        // Action combobox
        // Translate labels manually to let xgettext recognize them
        $action = new Element\Select('DeployAction');
        $action->setLabel('Action')
               ->setValueOptions(
                   array(
                        'launch' => $this->_('Download package, execute command, retrieve result'),
                        'execute' => $this->_('Optionally download package, execute command'),
                        'store' => $this->_('Just download package to target path'),
                   )
               );
        $this->add($action);

        // Command line or target path for action
        // Label is initialized by view helper and updated by JavaScript code.
        $actionParam = new Element\Text('ActionParam');
        $this->add($actionParam);
        $inputFilter->add(
            array(
                'name' => 'ActionParam',
                'required' => true,
            )
        );

        // Upload file
        $file = new Element\File('File');
        $file->setLabel('File');
        $this->add($file);
        $inputFilter->add(array('name' => 'File')); // Requirement is set in isValid()

        // Priority combobox
        $priority = new \Library\Form\Element\SelectSimple('Priority');
        $priority->setValueOptions(range(0, 10))
                 ->setLabel('Priority (0: exclusive, 10: lowest)');
        $this->add($priority);

        // Maximum fragment size.
        $maxFragmentSize = new Element\Text('MaxFragmentSize');
        $maxFragmentSize->setAttribute('size', '8')
                        ->setLabel('Maximum fragment size (kB)');
        $this->add($maxFragmentSize);
        $inputFilter->add(
            array(
                'name' => 'MaxFragmentSize',
                'required' => false,
                'filters' => [
                    $integerFilter,
                    [
                        'name' => 'ToNull',
                        'options' => ['type' => ToNull::TYPE_STRING],
                    ]
                ],
                'validators' => [$integerValidator],
            )
        );

        // Warn user before installation
        $warn = new Element\Checkbox('Warn');
        $warn->setLabel('Warn user');
        $this->add($warn);

        // Message to display to user before installation
        $warnMessage = new Element\Textarea('WarnMessage');
        $warnMessage->setLabel('Message');
        $this->add($warnMessage);
        $inputFilter->add(
            array(
                'name' => 'WarnMessage',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateNotificationMessage'),
                            'message' => $this->_('Message must not contain double quotes.'),
                        ),
                    ),
                ),
            )
        );

        // Countdown before installation starts automatically
        $warnCountdown = new Element\Text('WarnCountdown');
        $warnCountdown->setAttribute('size', '5')
                      ->setLabel('Countdown (seconds)');
        $this->add($warnCountdown);
        $inputFilter->add(
            array(
                'name' => 'WarnCountdown',
                'required' => false,
                'filters' => array($integerFilter),
                'validators' => array($integerValidator),
            )
        );

        // Allow user abort
        $warnAllowAbort = new Element\Checkbox('WarnAllowAbort');
        $warnAllowAbort->setLabel('Allow abort by user');
        $this->add($warnAllowAbort);

        // Allow user delay
        $warnAllowDelay = new Element\Checkbox('WarnAllowDelay');
        $warnAllowDelay->setLabel('Allow delay by user');
        $this->add($warnAllowDelay);

        // Message to display to user after deployment
        $postInstMessage = new Element\Textarea('PostInstMessage');
        $postInstMessage->setLabel('Post-installation message');
        $this->add($postInstMessage);
        $inputFilter->add(
            array(
                'name' => 'PostInstMessage',
                'required' => false,
                'filters' => array(
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name' => 'Callback',
                        'options' => array(
                            'callback' => array($this, 'validateNotificationMessage'),
                            'message' => $this->_('Message must not contain double quotes.'),
                        ),
                    ),
                ),
            )
        );

        // Submit button
        $submit = new \Library\Form\Element\Submit('Submit');
        $submit->setLabel('Build');
        $this->add($submit);

        $this->setInputFilter($inputFilter);
    }

    /**
     * Validation callback for notification messages
     *
     * @param string $value
     * @param array $context
     * @return bool
     * @internal
     */
    public function validateNotificationMessage($value, $context)
    {
        // The Windows agent handles notification messages through a separate
        // application (OcsNotifyUser.exe). Message strings and other parameters
        // are passed via command line. This application's command line parser
        // unconditionally treats double quotes as argument delimiters.
        // Arguments with (escaped) double quotes are not possible and would
        // lead to incorrect parsing results. This is avoided by forbidding
        // double quotes altogether.
        // Other agents do not support user notifications and ignore the
        // message. If the console user enters an invalid message and then
        // switches the platform to Linux or MacOS, the validation message would
        // be invisible. Validation always succeeds for these platforms to avoid
        // this problem.
        return $context['Platform'] != 'windows' or strpos($value, '"') === false;
    }

    /** {@inheritdoc} */
    public function setData($data)
    {
        $data['MaxFragmentSize'] = $this->localize(@$data['MaxFragmentSize'], 'integer');
        $data['WarnCountdown'] = $this->localize(@$data['WarnCountdown'], 'integer');
        return parent::setData($data);
    }

    public function isValid(): bool
    {
        $this->getInputFilter()->get('File')->setRequired(@$this->data['DeployAction'] != 'execute');
        return parent::isValid();
    }
}
