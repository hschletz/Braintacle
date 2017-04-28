<?php
/**
 * Import controller
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

namespace Tools\Controller;

/**
 * Import controller
 */
class Import
{
    /**
     * Client manager
     * @var \Model\Client\ClientManager
     */
    protected $_clientManager;

    /**
     * Constructor
     *
     * @param \Model\Client\ClientManager $clientManager
     */
    public function __construct(\Model\Client\ClientManager $clientManager)
    {
        $this->_clientManager = $clientManager;
    }

    /**
     * Import client from given file
     *
     * @param \ZF\Console\Route $route
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $this->_clientManager->importFile($route->getMatchedParam('filename'));
        return 0;
    }
}
