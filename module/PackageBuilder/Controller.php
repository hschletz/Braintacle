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
     * Build a package
     */
    public function packageBuilderAction()
    {
        $request = $this->getRequest();
        $name = $request->getParam('name');
        $file = $request->getParam('file');

        $serviceManager = $this->getServiceLocator();
        $config = $serviceManager->get('Model\Config');
        $serviceManager->get('Model\Package\PackageManager')->build(
            array(
                'Name' => $name,
                'Comment' => null,
                'FileName' => basename($file),
                'FileLocation' => $file,
                'Priority' => $config->defaultPackagePriority,
                'Platform' => $config->defaultPlatform,
                'DeployAction' => $config->defaultAction,
                'ActionParam' => $config->defaultActionParam,
                'Warn' => $config->defaultWarn,
                'WarnMessage' => $config->defaultWarnMessage,
                'WarnCountdown' => $config->defaultWarnCountdown,
                'WarnAllowAbort' => $config->defaultWarnAllowAbort,
                'WarnAllowDelay' => $config->defaultWarnAllowDelay,
                'PostInstMessage' => $config->defaultPostInstMessage,
                'MaxFragmentSize' => $config->defaultMaxFragmentSize,
            ),
            false
        );
        $this->console->writeLine('Package successfully built.');
    }
}
