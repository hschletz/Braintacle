<?php
/**
 * Form for display/setting of 'packages' preferences
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Forms
 */
/**
 * Form for display/setting of 'packages' preferences
 * @package Forms
 */
class Form_Preferences_Packages extends Form_Preferences
{

    /** {@inheritdoc} */
    protected $_types = array(
        'defaultDeployNonnotified' => 'bool',
        'defaultDeploySuccess' => 'bool',
        'defaultDeployNotified' => 'bool',
        'defaultDeployError' => 'bool',
        'defaultDeployGroups' => 'bool',
        'packagePath' => 'text',
        'defaultPlatform' => array(
            'windows' => 'Windows',
            'linux' => 'Linux',
            'mac' => 'MacOS'
        ),
        'defaultAction' => array(), // Translated content provided by init()
        'defaultActionParam' => 'text',
        'defaultPackagePriority' => array(), // Translated content provided by init()
        'defaultMaxFragmentSize' => 'integer',
        'defaultInfoFileLocation' => 'text',
        'defaultDownloadLocation' => 'text',
        'defaultCertificate' => 'text',
        'defaultWarn' => 'bool',
        'defaultWarnMessage' => 'clob',
        'defaultWarnCountdown' => 'integer',
        'defaultWarnAllowAbort' => 'bool',
        'defaultWarnAllowDelay' => 'bool',
        'defaultUserActionRequired' => 'bool',
        'defaultUserActionMessage' => 'clob',
    );

    /**
     * Translate labels before calling parent implementation, set up generated elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');

        // Create display group manually and add it first. This guarantees the
        // correct order of display. Elements are added later to the group, once
        // they are created by parent implementation.
        $deployGroup = new Zend_Form_DisplayGroup(
            'Deploy',
            $this->getPluginLoader(self::DECORATOR)
        );
        $deployGroup->setLegend('Defaults for deploying updated packages');
        $this->addDisplayGroups(array($deployGroup));

        // Translate and set elements for dropdown fields
        $this->_types['defaultAction'] = array(
            'launch' => $translate->_(
                'Download package, execute command, retrieve result'
            ),
            'execute' => $translate->_(
                'Optionally download package, execute command'
            ),
            'store' => $translate->_(
                'Just download package to target path'
            ),
        );
        $this->_types['defaultPackagePriority'] = array(
            '0 (' . $translate->_('may block other downloads!') . ')',
            '1 (' . $translate->_('high') . ')',
            2, 3, 4, 5, 6, 7, 8, 9,
            '10 (' . $translate->_('low') . ')'
        );

        // Translate labels
        $this->_labels = array(
            'packagePath' => $translate->_(
                'Path to package files (writeable by web server)'
            ),
            'defaultPlatform' => $translate->_(
                'Default platform'
            ),
            'defaultAction' => $translate->_(
                'Default action'
            ),
            'defaultActionParam' => $translate->_(
                'Default action parameter'
            ),
            'defaultPackagePriority' => $translate->_(
                'Default priority'
            ),
            'defaultMaxFragmentSize' => $translate->_(
                'Default maximum fragment size (kB), 0 for no fragmentation'
            ),
            'defaultInfoFileLocation' => $translate->_(
                'Default hostname/path for info file (HTTPS)'
            ),
            'defaultDownloadLocation' => $translate->_(
                'Default hostname/path for package download (HTTP)'
            ),
            'defaultCertificate' => $translate->_(
                'Default certificate'
            ),
            'defaultWarn' => $translate->_(
                'Warn user by default'
            ),
            'defaultWarnMessage' => $translate->_(
                'Default warn message'
            ),
            'defaultWarnCountdown' => $translate->_(
                'Default warn countdown (seconds)'
            ),
            'defaultWarnAllowAbort' => $translate->_(
                'Allow user abort by default'
            ),
            'defaultWarnAllowDelay' => $translate->_(
                'Allow user delay by default'
            ),
            'defaultUserActionRequired' => $translate->_(
                'User action required by default'
            ),
            'defaultUserActionMessage' => $translate->_(
                'Default user action message'
            ),
            'defaultDeployNonnotified' => $translate->_(
                'Not notified'
            ),
            'defaultDeploySuccess' => $translate->_(
                'Success'
            ),
            'defaultDeployNotified' => $translate->_(
                'Running'
            ),
            'defaultDeployError' => $translate->_(
                'Error'
            ),
            'defaultDeployGroups' => $translate->_(
                'Groups'
            ),
        );

        // Generate elements
        parent::init();

        // Move elements to display group
        $deployGroup->addElement($this->getElement('defaultDeployNonnotified'));
        $deployGroup->addElement($this->getElement('defaultDeploySuccess'));
        $deployGroup->addElement($this->getElement('defaultDeployNotified'));
        $deployGroup->addElement($this->getElement('defaultDeployError'));
        $deployGroup->addElement($this->getElement('defaultDeployGroups'));

        // Additional setup for elements
        $this->getElement('packagePath')
            ->addFilter('StringTrim')
            ->addValidator(new Braintacle_Validate_DirectoryWritable);
        $this->getElement('defaultInfoFileLocation')
            ->addFilter(
                'PregReplace',
                array(
                    array(
                        'match' => '/^.*:\/\//', // strip URI scheme
                        'replace' => ''
                    ),
                    ''
                )
            )
            ->addFilter('StringTrim', array('charlist' => '/'))
            ->addValidator(new Braintacle_Validate_Uri('https'));
        $this->getElement('defaultDownloadLocation')
            ->addFilter(
                'PregReplace',
                array(
                    array(
                        'match' => '/^.*:\/\//', // strip URI scheme
                        'replace' => ''
                    ),
                    ''
                )
            )
            ->addFilter('StringTrim', array('charlist' => '/'))
            ->addValidator(new Braintacle_Validate_Uri('https'));
        $this->getElement('defaultWarnCountdown')
            ->setAttrib('size', '5');
    }

}
