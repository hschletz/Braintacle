<?php
/**
 * Database controller
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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
 * Database controller
 */
class Database
{
    /**
     * Schema manager
     * @var \Database\SchemaManager
     */
    protected $_schemaManager;

    /**
     * Logger
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * Log writer
     * @var \Zend\Log\Writer\AbstractWriter
     */
    protected $_writer;

    /**
     * Constructor
     *
     * @param \Database\SchemaManager $schemaManager
     * @param \Zend\Log\Logger $logger
     * @param \Zend\Log\Writer\AbstractWriter $writer
     */
    public function __construct(
        \Database\SchemaManager $schemaManager,
        \Zend\Log\LoggerInterface $logger,
        \Zend\Log\Writer\AbstractWriter $writer
    ) {
        $this->_schemaManager = $schemaManager;
        $this->_logger = $logger;
        $this->_writer = $writer;
    }

    /**
     * Manage database schema
     *
     * @param \ZF\Console\Route $route
     * @param \Zend\Console\Adapter\AdapterInterface $console
     * @return integer Exit code
     */
    public function __invoke(\ZF\Console\Route $route, \Zend\Console\Adapter\AdapterInterface $console)
    {
        $loglevel = $route->getMatchedParam('loglevel', \Zend\Log\Logger::INFO);
        $prune = $route->getMatchedParam('prune') || $route->getMatchedParam('p');

        $this->_writer->addFilter('priority', ['priority' => $loglevel]);
        $this->_writer->setFormatter('simple', ['format' => '%priorityName%: %message%']);
        $this->_logger->addWriter($this->_writer);

        $this->_schemaManager->updateAll($prune);
        return 0;
    }
}
