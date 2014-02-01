<?php
/**
 * Form for creating a package
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
 * Form for creating a package
 *
 * The following fields are provided:
 *
 * - <b>Name</b> Name of the package, must be unique.
 * - <b>Comment</b> Package comment
 * - <b>Platform</b> Target platform, one of 'windows', 'linux' or 'mac'
 * - <b>DeployAction</b> Action to be performed by the agent, one of 'store', 'execute' or 'launch'
 * - <b>ActionParam</b> Path or command line, depending on action
 * - <b>FileName</b> Name of uploaded file (read only)
 * - <b>FileLocation</b> Path to temporary uploaded file (read only)
 * - <b>FileType</b> MIME type of uploaded file (read only)
 * - <b>Priority</b> Priority (0-10)
 * - <b>MaxFragmentSize</b> Maximum fragment size in kB
 * - <b>InfoFileUrlPath</b> HTTPS base URL
 * - <b>DownloadUrlPath</b> HTTP base URL
 * - <b>CertFile</b> Full path to HTTPS certificate
 * - <b>Warn</b> TRUE if a dialog should be displayed before installation
 * - <b>WarnMessage</b> Message to display
 * - <b>WarnCountdown</b> Timeout in seconds before installation continues
 * - <b>WarnAllowAbort</b> TRUE if user can abort installation
 * - <b>WarnAllowDelay</b> TRUE if user can postpone installation
 * - <b>UserActionRequired</b> TRUE if a dialog should be displayed after installation
 * - <b>UserActionMessage</b> Message to display
 * @package Forms
 */
class Form_Package extends Zend_Form
{

    /**
     * Create elements
     */
    public function init()
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $view = $this->getView();

        $this->setMethod('post');

        // Package name
        $name = new Zend_Form_Element_Text('Name');
        $name->addFilter('StringTrim')
             ->addValidator('StringLength', false, array(1, 255))
             ->addValidator(
                 'Db_NoRecordExists', false, array(
                    'table' => 'download_available',
                    'field' => 'name'
                )
             )
             ->setRequired(true)
             ->setLabel('Name');
        $this->addElement($name);

        // Comment
        $comment = new Zend_Form_Element_Text('Comment');
        $comment->addFilter('StringTrim')
             ->setLabel('Comment');
        $this->addElement($comment);

        // Platform combobox
        // No translation for OS names. To prevent complaints about missing
        // translations, translation for this element is disabled and the label
        // is translated manually.
        $platform = new Zend_Form_Element_Select('Platform');
        $platform->setDisableTranslator(true)
                 ->setMultiOptions(
                     array(
                        'windows' => 'Windows',
                        'linux' => 'Linux',
                        'mac' => 'MacOS'
                     )
                 )
                 ->setValue(Model_Config::get('DefaultPlatform'))
                 ->setLabel($translate->_('Platform'));
        $this->addElement($platform);

        // Action combobox
        // Translate labels manually to let xgettext recognize them
        $action = new Zend_Form_Element_Select('DeployAction');
        $action->setDisableTranslator(true)
               ->setMultiOptions(
                   array(
                        'launch' => $translate->_(
                            'Download package, execute command, retrieve result'
                        ),
                        'execute' => $translate->_(
                            'Optionally download package, execute command'
                        ),
                        'store' => $translate->_(
                            'Just download package to target path'
                        ),
                   )
               )
               ->setValue(Model_Config::get('DefaultAction'))
               ->setLabel($translate->_('Action'))
               ->setAttrib('onchange', 'changeParam();');
        $this->addElement($action);

        // Command line or target path for action
        // Real label is set by JavaScript code.
        // Use variable to prevent xgettext from scanning it.
        $actionParamLabel = 'Parameter';
        $actionParam = new Zend_Form_Element_Text('ActionParam');
        $actionParam->setDisableTranslator(true)
                    ->setValue(Model_Config::get('DefaultActionParam'))
                    ->setRequired(true)
                    ->setLabel($actionParamLabel);
        $this->addElement($actionParam);

        // Upload file
        // setRequired() is set in isValid()
        $maxFileSize = $this->_getMaxUploadSize();
        $measure = new Zend_Measure_Binary($maxFileSize / 1048576, 'MEGABYTE');
        $file = new Zend_Form_Element_File('File');
        $file->setMaxFileSize($maxFileSize)
             ->setValueDisabled(true)
             ->setIgnore(true)
             ->addValidator('Size', false, $maxFileSize)
             ->addValidator('Count', false, 1)
             ->setDisableTranslator(true)
             ->setLabel(sprintf($translate->_('File (max. %s)'), $measure->convertTo('MEGABYTE', 1)));
        $this->addElement($file);

        // Priority combobox
        // No translation for digits.
        $priority = new Zend_Form_Element_Select('Priority');
        $priority->setDisableTranslator(true)
                 ->setMultiOptions(
                     array(
                        '0 ('
                        . $translate->_('may block other downloads!')
                        . ')',

                        '1 (' . $translate->_('high') . ')',

                        2, 3, 4, 5, 6, 7, 8, 9,

                        '10 (' . $translate->_('low') . ')'
                    )
                 )
                 ->setValue(Model_Config::get('DefaultPackagePriority'))
                 ->setLabel($translate->_('Priority'));
        $this->addElement($priority);

        // Maximum fragment size.
        $maxFragmentSize = new Zend_Form_Element_Text('MaxFragmentSize');
        $maxFragmentSize->addValidator('StringLength', false, array(1, 8))
                        ->addValidator('Digits')
                        ->setValue(
                            Model_Config::get('DefaultMaxFragmentSize')
                        )
                        ->setRequired(true)
                        ->setAttrib('size', '8')
                        ->setLabel('Maximum fragment size (kB), enter 0 for no fragmentation');
        $this->addElement($maxFragmentSize);

        // HTTPS path to package metafile
        $infoFileUrl = new Zend_Form_Element_Text('InfoFileUrlPath');
        $infoFileUrl->addFilter('StringTrim')
                    ->addFilter(
                        'PregReplace',
                        array(
                            array(
                                'match' => '/^.*:\/\//', // strip URI scheme
                                'replace' => '',
                            ),
                            ''
                        )
                    )
                    ->addFilter('StringTrim', array('charlist' => '/'))
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator(new Braintacle_Validate_Uri('https'))
                    ->setRequired(true)
                    ->setValue(Model_Config::get('DefaultInfoFileLocation'))
                    ->setLabel('hostname/path for info file (HTTPS)');
        $this->addElement($infoFileUrl);

        // HTTP path to package download
        $downloadUrl = new Zend_Form_Element_Text('DownloadUrlPath');
        $downloadUrl->addFilter('StringTrim')
                    ->addFilter(
                        'PregReplace',
                        array(
                            array(
                                'match' => '/^.*:\/\//', // strip URI scheme
                                'replace' => '',
                            ),
                            ''
                        )
                    )
                    ->addFilter('StringTrim', array('charlist' => '/'))
                    ->addValidator('StringLength', false, array(1, 255))
                    ->addValidator(new Braintacle_Validate_Uri('http'))
                    ->setRequired(true)
                    ->setValue(Model_Config::get('DefaultDownloadLocation'))
                    ->setLabel('hostname/path for package download (HTTP)');
        $this->addElement($downloadUrl);

        // Local path to HTTPS Certificate
        $cert = new Zend_Form_Element_Text('CertFile');
        $cert->addFilter('StringTrim')
             ->addFilter(
                 'PregReplace',
                 array(
                      'match' => '/\\\\/',
                      'replace' => '/'
                      )
             ) // replace backslashes
             ->addValidator('StringLength', false, array(1, 255))
             ->addValidator('Regex', false, array('/\//')) // at least 1 /
             ->setRequired(true)
             ->setValue(Model_Config::get('DefaultCertificate'))
             ->setLabel('Certificate');
        $this->addElement($cert);

        // The next elements are only relevant and displayed when this one is checked.
        $warn = new Zend_Form_Element_Checkbox('Warn');
        $warn->setLabel('Warn user')
             ->setChecked(Model_Config::get('DefaultWarn'))
             ->setAttrib('onchange', 'toggleWarn();');
        $this->addElement($warn);

        // Message to display to user before deployment
        $warnMessage = new Zend_Form_Element_Textarea('WarnMessage');
        $warnMessage->addFilter('StringTrim')
                    ->setValue(Model_Config::get('DefaultWarnMessage'))
                    ->setLabel('Message');
        $this->addElement($warnMessage);

        // Countdown before deployment starts automatically
        $warnCountdown = new Zend_Form_Element_Text('WarnCountdown');
        $warnCountdown->addValidator('StringLength', false, array(1, 5))
                        ->addValidator('Digits')
                        ->setValue(
                            Model_Config::get('DefaultWarnCountdown')
                        )
                        ->setAttrib('size', '5')
                        ->setLabel('Countdown (seconds)');
        $this->addElement($warnCountdown);

        // Whether user may abort deployment
        $warnAllowAbort = new Zend_Form_Element_Checkbox('WarnAllowAbort');
        $warnAllowAbort->setLabel('Allow abort by user')
                       ->setValue(Model_Config::get('DefaultWarnAllowAbort'));
        $this->addElement($warnAllowAbort);

        // Whether user may delay deployment
        $warnAllowDelay = new Zend_Form_Element_Checkbox('WarnAllowDelay');
        $warnAllowDelay->setLabel('Allow delay by user')
                       ->setValue(Model_Config::get('DefaultWarnAllowDelay'));
        $this->addElement($warnAllowDelay);

        // The next element is only relevant and displayed when this one is checked.
        $userActionRequired = new Zend_Form_Element_Checkbox('UserActionRequired');
        $userActionRequired->setLabel('User action Required')
                           ->setChecked(Model_Config::get('DefaultUserActionRequired'))
                           ->setAttrib('onchange', 'toggleUserAction();');
        $this->addElement($userActionRequired);

        // Message to display to user after deployment
        $userActionMessage = new Zend_Form_Element_Textarea('UserActionMessage');
        $userActionMessage->addFilter('StringTrim')
                          ->setValue(Model_Config::get('DefaultUserActionMessage'))
                          ->setLabel('Message');
        $this->addElement($userActionMessage);


        // Submit button
        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setRequired(false)
               ->setIgnore(true)
               ->setLabel('Build');
        $this->addElement($submit);
    }

    /** Get Label for ActionParam fields
     * @param string $action One of 'launch', 'execute' or 'store'
     * @return string Localized label matching given action
     */
    protected function _getActionParamLabel($action)
    {
        $translate = Zend_Registry::get('Zend_Translate');

        switch ($action) {
        case "launch":
        case "execute":
            return $translate->_('Command line');
            break;
        case "store":
            return $translate->_('Target Path');
            break;
        }
    }

    /** Estimate maximum size for uploaded file based on php.ini settings.
     * @return integer Smallest value (in bytes) of the following:
     * 100% of upload_max_filesize
     * 90% of post_max_size
     * 80% of memory_limit
     */
    protected function _getMaxUploadSize()
    {
        $factors = array(
            'k' => 1024,
            'm' => 1048576,
            'g' => 1073741824,
        );

        $maxUpload = ini_get('upload_max_filesize');
        $unit = strtolower(substr($maxUpload, -1));
        if (array_key_exists($unit, $factors)) {
            $maxUpload = substr($maxUpload, 0, -1) * $factors[$unit];
        }

        $maxPost = ini_get('post_max_size');
        $unit = strtolower(substr($maxPost, -1));
        if (array_key_exists($unit, $factors)) {
            $maxPost = substr($maxPost, 0, -1) * $factors[$unit];
        }
        $maxPost *= .9;

        $maxMem = ini_get('memory_limit');
        if ($maxMem == '-1') {
            // Use dummy value if no limit is set
            $maxMem = $maxUpload + 1;
        } else {
            $unit = strtolower(substr($maxMem, -1));
            if (array_key_exists($unit, $factors)) {
                $maxMem = substr($maxMem, 0, -1) * $factors[$unit];
            }
            $maxMem *= .8;
        }

        return (int) min($maxUpload, $maxPost, $maxMem);
    }

    /**
     * Validate the form
     * @param array $data
     * @return boolean
     */
    public function isValid($data)
    {
        // File is only required for 'launch' and 'store', optional for 'execute'.
        $this->getElement('File')->setRequired(
            $data['DeployAction'] != 'execute'
        );
        // now validate it
        return parent::isValid($data);
    }

    /**
     * Retrieve all form element values
     * @param bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        // Provide FileName, FileLocation and FileType properties
        $values = parent::getValues($suppressArrayNotation);
        $fileInfo = $this->getElement('File')->getFileInfo();
        $values['FileName'] = $fileInfo['File']['name'];
        $values['FileLocation'] = $fileInfo['File']['tmp_name'];
        $values['FileType'] = $fileInfo['File']['type'];
        return $values;
    }

    /**
     * Render form
     * @param Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view=null)
    {
        $view = $this->getView();

        // Generate JavaScript to make this form fully functional.
        $displayErrors = ini_get('display_errors');
        // Don't let missing translations screw up the JS code
        ini_set('display_errors', false);

        $view->headScript()->captureStart();
        ?>

        /* Event handler for Action combobox.
           Changes label of parameter input field according to selected action.
        */
        function changeParam()
        {
            var label;

            switch (document.getElementById("DeployAction").value) {
            case "launch":
                label = "<?php print $this->_getActionParamLabel('launch'); ?>";
                break;
            case "execute":
                label = "<?php print $this->_getActionParamLabel('execute'); ?>";
                break;
            case "store":
                label = "<?php print $this->_getActionParamLabel('store'); ?>";
                break;
            }

            document.getElementById("ActionParam-label").firstChild.innerHTML = label;
        }

        // Hide or display a block element.
        function display(id, display)
        {
            if (display) {
                display = "block";
            } else {
                display = "none";
            }
            document.getElementById(id+"-label").style.display = display;
            document.getElementById(id+"-element").style.display = display;
        }

        /* Event handler for Warn checkbox. Also called by init().
           Hides or displays Warn* fields according to checked state.
        */
        function toggleWarn()
        {
            var checked = document.getElementById("Warn").checked;
            display("WarnMessage", checked);
            display("WarnCountdown", checked);
            display("WarnAllowAbort", checked);
            display("WarnAllowDelay", checked);
        }

        /* Event handler for UserActionRequired checkbox. Also called by init().
           Hides or displays UserActionMessage field according to checked state.
        */
        function toggleUserAction()
        {
            display("UserActionMessage", document.getElementById("UserActionRequired").checked);
        }

        /* Called by body.onload().
           Hides fields according to checkbox state and sets ActionParam label.
        */
        function init()
        {
            toggleWarn();
            toggleUserAction();
            changeParam();
            document.getElementById("Name").focus();
        }

        <?php
        $view->headScript()->captureEnd();
        ini_set('display_errors', $displayErrors);

        return parent::render($view);
    }

}
