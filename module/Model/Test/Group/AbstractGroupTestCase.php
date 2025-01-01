<?php

/**
 * Test setup for Tests on GroupInfo table
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Test\Group;

use Database\SchemaManager;
use Laminas\Db\Adapter\Adapter;
use Model\Config;
use Model\Group\Group;
use Model\Test\AbstractTestCase;
use Nada\Database\AbstractDatabase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractGroupTestCase extends AbstractTestCase
{
    protected $_config;

    protected $_groupInfo;

    public function setUp(): void
    {
        // GroupInfo::initialize() table has a dependency on Model\Config which
        // can have side effects on other tests. For better test isolation, set
        // up a GroupInfo instance with a Model\Config mock object. Every test
        // that relies on the GroupInfo table should override the
        // Database\Table\GroupInfo service with $this->_groupInfo.
        // The setup is done only once, but cannot be done in setUpBeforeClass()
        // because mock objects cannot be created in a static method.
        if (!$this->_config) {
            $this->_config = $this->createMock('Model\Config');
            $this->_config->method('__get')->willReturnMap(array(array('groupCacheExpirationInterval', 30)));

            $logger = $this->createStub(LoggerInterface::class);

            $container = $this->createStub(ContainerInterface::class);
            $container->method('get')->willReturnMap([
                [LoggerInterface::class, $logger],
            ]);
            $schemaManager = new SchemaManager($container);

            $serviceManager = $this->createStub(ContainerInterface::class);
            $serviceManager->method('get')->willReturnMap([
                [Adapter::class, static::$serviceManager->get(Adapter::class)],
                [AbstractDatabase::class, static::$serviceManager->get(AbstractDatabase::class)],
                [Config::class, $this->_config],
                [Group::class, static::$serviceManager->get(Group::class)],
                [LoggerInterface::class, $logger],
                [SchemaManager::class, $schemaManager],
            ]);

            $this->_groupInfo = new \Database\Table\GroupInfo($serviceManager);
            $this->_groupInfo->updateSchema(true);
            $this->_groupInfo->initialize();
        }
        parent::setUp();
    }
}
