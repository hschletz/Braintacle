<?php

/**
 * Factory for main navigation menu
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

namespace Console\Navigation;

/**
 * Factory for main navigation menu
 */
class MainMenuFactory extends \Library\Navigation\AbstractNavigationFactory
{
    /** {@inheritdoc} */
    protected function getConfig()
    {
        return array(
            array(
                'label' => $this->_('Inventory'),
                'controller' => 'client',
                'action' => 'index',
                'pages' => array(
                    array(
                        'label' => $this->_('Clients'),
                        'controller' => 'client',
                        'action' => 'index',
                    ),
                    array(
                        'label' => $this->_('Software'),
                        'controller' => 'software',
                        'action' => 'index',
                    ),
                    array(
                        'label' => $this->_('Network'),
                        'controller' => 'network',
                        'action' => 'index',
                        ),
                    array(
                        'label' => $this->_('Duplicates'),
                        'controller' => 'duplicates',
                        'action' => 'index',
                    ),
                    array(
                        'label' => $this->_('Import'),
                        'controller' => 'client',
                        'action' => 'import',
                    ),
                ),
            ),
            array(
                'label' => $this->_('Groups'),
                'controller' => 'group',
                'action' => 'index',
            ),
            array(
                'label' => $this->_('Packages'),
                'controller' => 'package',
                'action' => 'index',
                'pages' => array(
                    array(
                        'label' => $this->_('Overview'),
                        'controller' => 'package',
                        'action' => 'index',
                    ),
                    array(
                        'label' => $this->_('Build'),
                        'controller' => 'package',
                        'action' => 'build',
                    ),
                ),
            ),
            array(
                'label' => $this->_('Licenses'),
                'controller' => 'licenses',
                'action' => 'index',
            ),
            array(
                'label' => $this->_('Search'),
                'controller' => 'client',
                'action' => 'search',
            ),
            array(
                'label' => $this->_('Preferences'),
                'controller' => 'preferences',
                'action' => 'index',
                'pages' => array(
                    array(
                        'label' => $this->_('Display'),
                        'controller' => 'preferences',
                        'action' => 'display',
                    ),
                    array(
                        'label' => $this->_('Inventory'),
                        'controller' => 'preferences',
                        'action' => 'inventory',
                    ),
                    array(
                        'label' => $this->_('Agent'),
                        'controller' => 'preferences',
                        'action' => 'agent',
                    ),
                    array(
                        'label' => $this->_('Packages'),
                        'controller' => 'preferences',
                        'action' => 'packages',
                    ),
                    array(
                        'label' => $this->_('Download'),
                        'controller' => 'preferences',
                        'action' => 'download',
                    ),
                    array(
                        'label' => $this->_('Groups'),
                        'controller' => 'preferences',
                        'action' => 'groups',
                    ),
                    array(
                        'label' => $this->_('Network scanning'),
                        'controller' => 'preferences',
                        'action' => 'networkscanning',
                    ),
                    array(
                        'label' => $this->_('Raw data'),
                        'controller' => 'preferences',
                        'action' => 'rawdata',
                    ),
                    array(
                        'label' => $this->_('Filters'),
                        'controller' => 'preferences',
                        'action' => 'filters',
                    ),
                    array(
                        'label' => $this->_('System'),
                        'controller' => 'preferences',
                        'action' => 'system',
                    ),
                    array(
                        'label' => $this->_('Users'),
                        'controller' => 'accounts',
                        'action' => 'index',
                    ),
                ),
            ),
        );
    }
}
