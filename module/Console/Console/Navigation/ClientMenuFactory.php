<?php
/**
 * Factory for client navigation menu
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

namespace Console\Navigation;

/**
 * Factory for client navigation menu
 *
 * Windows-specific pages have a custom property "windowsOnly" set to TRUE.
 */
class ClientMenuFactory extends \Library\Navigation\AbstractNavigationFactory
{
    /**
     * @internal
     */
    protected function getPages(\Zend\ServiceManager\ServiceLocatorInterface $serviceLocator)
    {
        parent::getPages($serviceLocator);
        $id = array('id' => $serviceLocator->get('Request')->getQuery('id'));
        foreach ($this->pages as &$page) {
            $page['query'] = $id;
        }
        return $this->pages;
    }

    /** {@inheritdoc} */
    protected function _getConfig()
    {
        return array(
            array(
                'label' => $this->_('General'),
                'controller' => 'client',
                'action' => 'general',
            ),
            array(
                'label' => $this->_('Windows'),
                'controller' =>'client',
                'action' => 'windows',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Network'),
                'controller' =>'client',
                'action' => 'network',
            ),
            array(
                'label' => $this->_('Storage'),
                'controller' =>'client',
                'action' => 'storage',
            ),
            array(
                'label' => $this->_('Display'),
                'controller' =>'client',
                'action' => 'display',
            ),
            array(
                'label' => $this->_('BIOS'),
                'controller' =>'client',
                'action' => 'bios',
            ),
            array(
                'label' => $this->_('System'),
                'controller' =>'client',
                'action' => 'system',
            ),
            array(
                'label' => $this->_('Printers'),
                'controller' =>'client',
                'action' => 'printers',
            ),
            array(
                'label' => $this->_('Software'),
                'controller' =>'client',
                'action' => 'software',
            ),
            array(
                'label' => $this->_('MS Office'),
                'controller' =>'client',
                'action' => 'msoffice',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Registry'),
                'controller' =>'client',
                'action' => 'registry',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Virtual machines'),
                'controller' =>'client',
                'action' => 'virtualmachines',
            ),
            array(
                'label' => $this->_('Misc'),
                'controller' =>'client',
                'action' => 'misc',
            ),
            array(
                'label' => $this->_('User defined'),
                'controller' =>'client',
                'action' => 'customfields',
            ),
            array(
                'label' => $this->_('Packages'),
                'controller' =>'client',
                'action' => 'packages',
            ),
            array(
                'label' => $this->_('Groups'),
                'controller' =>'client',
                'action' => 'groups',
            ),
            array(
                'label' => $this->_('Configuration'),
                'controller' =>'client',
                'action' => 'configuration',
            ),
            array(
                'label' => $this->_('Export'),
                'controller' =>'client',
                'action' => 'export',
            ),
            array(
                'label' => $this->_('Delete'),
                'controller' =>'client',
                'action' => 'delete',
            )
        );
    }
}
