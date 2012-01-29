<?php
/**
 * Render headline and navigation for inventory details
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package ViewHelpers
 */
/**
 * Render headline and navigation for inventory details
 * @package ViewHelpers
 */
class Zend_View_Helper_InventoryHeader extends Zend_View_Helper_Abstract
{

    /**
     * Render headline and navigation for inventory details
     * @param Model_Computer Computer for which inventory is displayed
     * @return string HTML code with header and navigation
     */
    function inventoryHeader($computer)
    {
        $output = $this->view->htmlTag(
            'h1',
            sprintf(
                $this->view->translate('Inventory of \'%s\''),
                $this->view->escape($computer->getName())
            )
        );

        $id = array('id' => $computer->getId());
        $navigation = new Zend_Navigation;

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('General')
             ->setController('computer')
             ->setAction('general')
             ->setParams($id);
        $navigation->addPage($page);

        if ($computer->isWindows()) {
            $page = new Zend_Navigation_Page_Mvc;
            $page->setLabel('Windows')
                 ->setController('computer')
                 ->setAction('windows')
                 ->setParams($id);
            $navigation->addPage($page);
        }

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Network')
             ->setController('computer')
             ->setAction('network')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Storage')
             ->setController('computer')
             ->setAction('storage')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Display')
             ->setController('computer')
             ->setAction('display')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('BIOS')
             ->setController('computer')
             ->setAction('bios')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('System')
             ->setController('computer')
             ->setAction('system')
             ->setParams($id);
        $navigation->addPage($page);

        if ($computer->isWindows()) {
            $page = new Zend_Navigation_Page_Mvc;
            $page->setLabel('Printers')
                 ->setController('computer')
                 ->setAction('printers')
                 ->setParams($id);
            $navigation->addPage($page);
        }

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Software')
             ->setController('computer')
             ->setAction('software')
             ->setParams($id);
        $navigation->addPage($page);

        if ($computer->isWindows()) {
            $page = new Zend_Navigation_Page_Mvc;
            $page->setLabel('Registry')
                 ->setController('computer')
                 ->setAction('registry')
                 ->setParams($id);
            $navigation->addPage($page);
        }

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('VMs')
             ->setController('computer')
             ->setAction('vms')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Misc')
             ->setController('computer')
             ->setAction('misc')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('User defined')
             ->setController('computer')
             ->setAction('userdefined')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Packages')
             ->setController('computer')
             ->setAction('packages')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Groups')
             ->setController('computer')
             ->setAction('groups')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Export')
             ->setController('computer')
             ->setAction('export')
             ->setParams($id);
        $navigation->addPage($page);

        $page = new Zend_Navigation_Page_Mvc;
        $page->setLabel('Delete')
             ->setController('computer')
             ->setAction('delete')
             ->setParams($id);
        $navigation->addPage($page);

        $output .= $this->view->navigation()
            ->menu()
            ->setUlClass('navigation navigation_details')
            ->render($navigation);
        return $output;
    }

}
