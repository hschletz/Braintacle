<?php
/**
 * Form for display/setting of 'packages' preferences
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
        'DefaultDeployNonnotified' => 'bool',
        'DefaultDeploySuccess' => 'bool',
        'DefaultDeployNotified' => 'bool',
        'DefaultDeployError' => 'bool',
        'DefaultDeployGroups' => 'bool',
        'PackagePath' => 'text',
        'DefaultPlatform' => array(
            'windows' => 'Windows',
            'linux' => 'Linux',
            'mac' => 'MacOS'
        ),
        'DefaultAction' => array(), // Translated content provided by init()
        'DefaultActionParam' => 'text',
        'DefaultPackagePriority' => array(), // Translated content provided by init()
        'DefaultMaxFragmentSize' => 'integer',
        'DefaultInfoFileLocation' => 'text',
        'DefaultDownloadLocation' => 'text',
        'DefaultCertificate' => 'text',
        'DefaultWarn' => 'bool',
        'DefaultWarnMessage' => 'clob',
        'DefaultWarnCountdown' => 'integer',
        'DefaultWarnAllowAbort' => 'bool',
        'DefaultWarnAllowDelay' => 'bool',
        'DefaultUserActionRequired' => 'bool',
        'DefaultUserActionMessage' => 'clob',
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
        $this->_types['DefaultAction'] = array(
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
        $this->_types['DefaultPackagePriority'] = array(
            '0 (' . $translate->_('may block other downloads!') . ')',
            '1 (' . $translate->_('high') . ')',
            2, 3, 4, 5, 6, 7, 8, 9,
            '10 (' . $translate->_('low') . ')'
        );

        // Translate labels
        $this->_labels = array(
            'PackagePath' => $translate->_(
                'Path to package files (writeable by web server)'
            ),
            'DefaultPlatform' => $translate->_(
                'Default platform'
            ),
            'DefaultAction' => $translate->_(
                'Default action'
            ),
            'DefaultActionParam' => $translate->_(
                'Default action parameter'
            ),
            'DefaultPackagePriority' => $translate->_(
                'Default priority'
            ),
            'DefaultMaxFragmentSize' => $translate->_(
                'Default maximum fragment size (kB), 0 for no fragmentation'
            ),
            'DefaultInfoFileLocation' => $translate->_(
                'Default hostname/path for info file (HTTPS)'
            ),
            'DefaultDownloadLocation' => $translate->_(
                'Default hostname/path for package download (HTTP)'
            ),
            'DefaultCertificate' => $translate->_(
                'Default certificate'
            ),
            'DefaultWarn' => $translate->_(
                'Warn user by default'
            ),
            'DefaultWarnMessage' => $translate->_(
                'Default warn message'
            ),
            'DefaultWarnCountdown' => $translate->_(
                'Default warn countdown (seconds)'
            ),
            'DefaultWarnAllowAbort' => $translate->_(
                'Allow user abort by default'
            ),
            'DefaultWarnAllowDelay' => $translate->_(
                'Allow user delay by default'
            ),
            'DefaultUserActionRequired' => $translate->_(
                'User action required by default'
            ),
            'DefaultUserActionMessage' => $translate->_(
                'Default user action message'
            ),
            'DefaultDeployNonnotified' => $translate->_(
                'Not notified'
            ),
            'DefaultDeploySuccess' => $translate->_(
                'Success'
            ),
            'DefaultDeployNotified' => $translate->_(
                'Running'
            ),
            'DefaultDeployError' => $translate->_(
                'Error'
            ),
            'DefaultDeployGroups' => $translate->_(
                'Groups'
            ),
        );

        // Generate elements
        parent::init();

        // Move elements to display group
        $deployGroup->addElement($this->getElement('DefaultDeployNonnotified'));
        $deployGroup->addElement($this->getElement('DefaultDeploySuccess'));
        $deployGroup->addElement($this->getElement('DefaultDeployNotified'));
        $deployGroup->addElement($this->getElement('DefaultDeployError'));
        $deployGroup->addElement($this->getElement('DefaultDeployGroups'));

        // Additional setup for elements
        $this->getElement('PackagePath')
            ->addFilter('StringTrim')
            ->addValidator('Regex', false, array('pattern' => '#[/\\\\]download[/\\\\]?$#'))
            ->addValidator(new Braintacle_Validate_DirectoryWritable);
        $this->getElement('DefaultInfoFileLocation')
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
        $this->getElement('DefaultDownloadLocation')
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
        $this->getElement('DefaultWarnCountdown')
            ->setAttrib('size', '5');
    }

}
