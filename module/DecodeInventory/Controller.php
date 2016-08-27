<?php
/**
 * DecodeInventory application controller
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

namespace DecodeInventory;

/**
 * DecodeInventory application controller
 */
class Controller extends \Zend\Mvc\Controller\AbstractConsoleController
{
    /**
     * Filter instance
     * @var \Protocol\Filter\InventoryDecode
     */
    protected $_inventoryDecode;

    /**
     * Constructor
     *
     * @param \Protocol\Filter\InventoryDecode $inventoryDecode
     */
    public function __construct(\Protocol\Filter\InventoryDecode $inventoryDecode)
    {
        $this->_inventoryDecode = $inventoryDecode;
    }

    /**
     * Decode a compressed inventory file
     */
    public function decodeInventoryAction()
    {
        $input = $this->getRequest()->getParam('input_file');

        if (!is_file($input) or !is_readable($input)) {
            $model = new \Zend\View\Model\ConsoleModel;
            $model->setErrorLevel(10);
            $model->setResult("Input file does not exist or is not readable.\n");
            return $model;
        }

        try {
            return $this->_inventoryDecode->filter(\Library\FileObject::fileGetContents($input));
        } catch (\InvalidArgumentException $e) {
            $model = new \Zend\View\Model\ConsoleModel;
            $model->setErrorLevel(11);
            $model->setResult($e->getMessage() . "\n");
            return $model;
        }
    }
}
