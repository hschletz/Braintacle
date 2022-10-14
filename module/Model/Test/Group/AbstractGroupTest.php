<?php

/**
 * Test setup for Tests on GroupInfo table
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

namespace Model\Test\Group;

use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\MockObject\Stub;

abstract class AbstractGroupTest extends \Model\Test\AbstractTest
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

            /** @var Stub|ServiceLocatorInterface */
            $serviceManager = $this->createStub(ServiceLocatorInterface::class);
            $serviceManager->method('get')->willReturnMap(
                array(
                    array('Db', static::$serviceManager->get('Db')),
                    array('Database\Nada', static::$serviceManager->get('Database\Nada')),
                    array('Library\Logger', static::$serviceManager->get('Library\Logger')),
                    array('Model\Config', $this->_config),
                    array('Model\Group\Group', static::$serviceManager->get('Model\Group\Group')),
                )
            );

            $this->_groupInfo = new \Database\Table\GroupInfo($serviceManager);
            $this->_groupInfo->updateSchema(true);
            $this->_groupInfo->initialize();
        }
        parent::setUp();
    }
}
