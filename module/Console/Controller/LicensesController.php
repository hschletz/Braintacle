<?php

/**
 * Controller for managing software licenses
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

namespace Console\Controller;

/**
 * Controller for managing software licenses
 */
class LicensesController extends \Laminas\Mvc\Controller\AbstractActionController
{
    /**
     * Software manager
     *
     * @var \Model\SoftwareManager
     */
    protected $_softwareManager;

    /**
     * Constructor
     *
     * @param \Model\SoftwareManager $softwareManager
     */
    public function __construct(\Model\SoftwareManager $softwareManager)
    {
        $this->_softwareManager = $softwareManager;
    }

    /**
     * Display overview of software licenses
     *
     * @return array windowsProductKeys => number of manually entered keys
     */
    public function indexAction()
    {
        return array(
            'windowsProductKeys' => $this->_softwareManager->getNumManualProductKeys()
        );
    }
}
