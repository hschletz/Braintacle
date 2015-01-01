<?php
/**
 * Factory for computer navigation menu
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
 * Factory for computer navigation menu
 *
 * Windows-specific pages have a custom property "windowsOnly" set to TRUE.
 */
class ComputerMenuFactory extends \Library\Navigation\AbstractNavigationFactory
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
                'controller' => 'computer',
                'action' => 'general',
            ),
            array(
                'label' => $this->_('Windows'),
                'controller' =>'computer',
                'action' => 'windows',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Network'),
                'controller' =>'computer',
                'action' => 'network',
            ),
            array(
                'label' => $this->_('Storage'),
                'controller' =>'computer',
                'action' => 'storage',
            ),
            array(
                'label' => $this->_('Display'),
                'controller' =>'computer',
                'action' => 'display',
            ),
            array(
                'label' => $this->_('BIOS'),
                'controller' =>'computer',
                'action' => 'bios',
            ),
            array(
                'label' => $this->_('System'),
                'controller' =>'computer',
                'action' => 'system',
            ),
            array(
                'label' => $this->_('Printers'),
                'controller' =>'computer',
                'action' => 'printers',
            ),
            array(
                'label' => $this->_('Software'),
                'controller' =>'computer',
                'action' => 'software',
            ),
            array(
                'label' => $this->_('MS Office'),
                'controller' =>'computer',
                'action' => 'msoffice',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Registry'),
                'controller' =>'computer',
                'action' => 'registry',
                'windowsOnly' => true,
            ),
            array(
                'label' => $this->_('Virtual machines'),
                'controller' =>'computer',
                'action' => 'virtualmachines',
            ),
            array(
                'label' => $this->_('Misc'),
                'controller' =>'computer',
                'action' => 'misc',
            ),
            array(
                'label' => $this->_('User defined'),
                'controller' =>'computer',
                'action' => 'customfields',
            ),
            array(
                'label' => $this->_('Packages'),
                'controller' =>'computer',
                'action' => 'packages',
            ),
            array(
                'label' => $this->_('Groups'),
                'controller' =>'computer',
                'action' => 'groups',
            ),
            array(
                'label' => $this->_('Configuration'),
                'controller' =>'computer',
                'action' => 'configuration',
            ),
            array(
                'label' => $this->_('Export'),
                'controller' =>'computer',
                'action' => 'export',
            ),
            array(
                'label' => $this->_('Delete'),
                'controller' =>'computer',
                'action' => 'delete',
            )
        );
    }
}
