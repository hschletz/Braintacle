<?php
/**
 * Abstract test case for naming strategies
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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

namespace Database\Test\Hydrator\NamingStrategy;

abstract class AbstractNamingStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * NamingStrategy instance
     * @var \Zend\Stdlib\Hydrator\NamingStrategy\NamingStrategyInterface
     */
    protected $_namingStrategy;

    public function setUp()
    {
        $class = get_class($this);
        $class = '\Database\Hydrator\NamingStrategy' . substr($class, strrpos($class, '\\'));
        $this->_namingStrategy = new $class;
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            'Zend\Stdlib\Hydrator\NamingStrategy\NamingStrategyInterface',
            $this->_namingStrategy
        );
    }

    /**
     * @dataProvider hydrateProvider
     */
    public function testHydrate($name, $expected)
    {
        $this->assertEquals($expected, $this->_namingStrategy->hydrate($name));
    }

    /**
     * @dataProvider extractProvider
     */
    public function testExtract($name, $expected)
    {
        $this->assertEquals($expected, $this->_namingStrategy->extract($name));
    }
}
