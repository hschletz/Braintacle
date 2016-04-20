<?php
/**
 * Database manager application controller
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

namespace DatabaseManager;

/**
 * Database manager application controller
 */
class Controller extends \Zend\Mvc\Controller\AbstractConsoleController
{
    /**
     * Schema manager
     * @var \Database\SchemaManager
     */
    protected $_schemaManager;

    /**
     * Logger
     * @var \Zend\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * Constructor
     *
     * @param \Database\SchemaManager $schemaManager
     * @param \Zend\Log\LoggerInterface $logger
     */
    public function __construct(\Database\SchemaManager $schemaManager, \Zend\Log\LoggerInterface $logger)
    {
        $this->_schemaManager = $schemaManager;
        $this->_logger = $logger;
    }

    /**
     * Manage database schema
     */
    public function schemaManagerAction()
    {
        // Set up logger. Log level is already validated by route.
        $priority = \Zend\Filter\StaticFilter::execute(
            $this->getRequest()->getParam('loglevel', 'info'),
            'Library\LogLevel'
        );
        $writer = new \Zend\Log\Writer\Stream('php://stderr');
        $writer->addFilter('Priority', array('priority' => $priority));
        $writer->setFormatter('Simple', array('format' => '%priorityName%: %message%'));
        $this->_logger->addWriter($writer);

        $this->_schemaManager->updateAll();
    }
}
