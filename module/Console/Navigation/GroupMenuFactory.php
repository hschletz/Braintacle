<?php
/**
 * Factory for group navigation menu
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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
 * Factory for group navigation menu
 */
class GroupMenuFactory extends \Library\Navigation\AbstractNavigationFactory
{
    /** {@inheritdoc} */
    protected function getPages(\Interop\Container\ContainerInterface $container)
    {
        parent::getPages($container);
        $name = array('name' => $container->get('Request')->getQuery('name'));
        foreach ($this->pages as &$page) {
            $page['query'] = $name;
        }
        return $this->pages;
    }

    /** {@inheritdoc} */
    protected function _getConfig()
    {
        return array(
            array(
                'label' => $this->_('General'),
                'controller' => 'group',
                'action' => 'general',
            ),
            array(
                'label' => $this->_('Members'),
                'controller' =>'group',
                'action' => 'members',
            ),
            array(
                'label' => $this->_('Excluded'),
                'controller' =>'group',
                'action' => 'excluded',
            ),
            array(
                'label' => $this->_('Packages'),
                'controller' =>'group',
                'action' => 'packages',
            ),
            array(
                'label' => $this->_('Configuration'),
                'controller' =>'group',
                'action' => 'configuration',
            ),
            array(
                'label' => $this->_('Delete'),
                'controller' =>'group',
                'action' => 'delete',
            )
        );
    }
}
