<?php
/**
 * Package builder application controller
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

namespace PackageBuilder;

/**
 * Package builder application controller
 */
class Controller extends \Zend\Mvc\Controller\AbstractConsoleController
{
    /**
     * Config
     * @var \Model\Config
     */
    protected $_config;

    /**
     * Package manager
     * @var \Model\Package\PackageManager
     */
    protected $_packageManager;

    /**
     * Constructor
     *
     * @param \Model\Config $config
     * @param \Model\Package\PackageManager $packageManager
     */
    public function __construct(\Model\Config $config, \Model\Package\PackageManager $packageManager)
    {
        $this->_config = $config;
        $this->_packageManager = $packageManager;
    }

    /**
     * Build a package
     */
    public function packageBuilderAction()
    {
        $request = $this->getRequest();
        $name = $request->getParam('name');
        $file = $request->getParam('file');

        $this->_packageManager->buildPackage(
            array(
                'Name' => $name,
                'Comment' => null,
                'FileName' => basename($file),
                'FileLocation' => $file,
                'Priority' => $this->_config->defaultPackagePriority,
                'Platform' => $this->_config->defaultPlatform,
                'DeployAction' => $this->_config->defaultAction,
                'ActionParam' => $this->_config->defaultActionParam,
                'Warn' => $this->_config->defaultWarn,
                'WarnMessage' => $this->_config->defaultWarnMessage,
                'WarnCountdown' => $this->_config->defaultWarnCountdown,
                'WarnAllowAbort' => $this->_config->defaultWarnAllowAbort,
                'WarnAllowDelay' => $this->_config->defaultWarnAllowDelay,
                'PostInstMessage' => $this->_config->defaultPostInstMessage,
                'MaxFragmentSize' => $this->_config->defaultMaxFragmentSize,
            ),
            false
        );
        $this->console->writeLine('Package successfully built.');
    }
}
