<?php

/**
 * Controller for all package related actions
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

namespace Console\Controller;

use Braintacle\Legacy\Controller;
use Braintacle\Legacy\MvcEvent;
use Braintacle\Legacy\Response;

/**
 * Controller for all package related actions
 */
class PackageController extends Controller
{
    /**
     * Package manager
     * @var \Model\Package\PackageManager
     */
    protected $_packageManager;

    public function __construct(
        \Model\Package\PackageManager $packageManager,
    ) {
        $this->_packageManager = $packageManager;
    }

    public function onDispatch(MvcEvent $e)
    {
        $this->getEvent()->setParam('template', 'MainMenu/PackagesMenuLayout.latte');

        return parent::onDispatch($e);
    }

    /**
     * Delete a package
     *
     * Query params: name
     *
     * @return array|Response array(name) or redirect response
     */
    public function deleteAction()
    {
        $name = $this->params()->fromQuery('name');

        if ($this->getRequest()->isPost()) {
            if ($this->params()->fromPost('yes')) {
                $flashMessenger = $this->flashMessenger();
                try {
                    $this->_packageManager->deletePackage($name);
                    $flashMessenger->addSuccessMessage(
                        sprintf($this->_("Package '%s' was successfully deleted."), $name)
                    );
                } catch (\Model\Package\RuntimeException $e) {
                    $flashMessenger->addErrorMessage($e->getMessage());
                }
            }
            return $this->redirectToRoute('packagesList');
        } else {
            return array('name' => $name);
        }
    }
}
