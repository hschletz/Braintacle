<?php

/**
 * Set active Page in main menu
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

namespace Console\Mvc\Controller\Plugin;

/**
 * Set active Page in main menu
 *
 * This plugin can be used by actions that are not matched by a route in the
 * menu structure to mark a given page as active and cause the corresponding
 * submenu to be rendered. Actions that can be reached directly via a menu
 * button do not need this.
 */
class SetActiveMenu extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Navigation structure to operate on
     * @var \Laminas\Navigation\Navigation
     */
    protected $_navigation;

    /**
     * Constructor
     *
     * @param \Laminas\Navigation\Navigation $navigation
     */
    public function __construct(\Laminas\Navigation\Navigation $navigation)
    {
        $this->_navigation = $navigation;
    }

    /**
     * Mark a given page as active
     *
     * @param string $mainPage Label of top menu page
     * @param string $subPage Optional: label of submenu page
     * @throws \InvalidArgumentException if the given page does not exist
     */
    public function __invoke($mainPage, $subPage = null)
    {
        // Search $mainPage label in top level menu only.
        // Don't use findOneByLabel() because that would search in submenus too.
        foreach ($this->_navigation as $page) {
            if ($page->getLabel() == $mainPage) {
                $currentPage = $page;
                break;
            }
        }
        if (!isset($currentPage)) {
            throw new \InvalidArgumentException('Invalid top menu page: ' . $mainPage);
        }
        if ($subPage) {
            $currentPage = $currentPage->findOneByLabel($subPage);
            if (!$currentPage) {
                throw new \InvalidArgumentException('Invalid submenu page: ' . $subPage);
            }
        }
        $currentPage->setActive();
    }
}
