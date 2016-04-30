<?php
/**
 * Factory for PackageController
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

namespace Console\Service;

/**
 * Factory for PackageController
 */
class PackageControllerFactory implements \Zend\ServiceManager\FactoryInterface
{
    /**
     * @internal
     */
    public function createService(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        $serviceManager = $serviceLocator->getServiceLocator();
        $formManager = $serviceManager->get('FormElementManager');
        return new \Console\Controller\PackageController(
            $serviceManager->get('Model\Package\PackageManager'),
            $serviceManager->get('Model\Config'),
            $formManager->get('Console\Form\Package\Build'),
            $formManager->get('Console\Form\Package\Update')
        );
    }
}