<?php

/**
 * Tests for GetOrder controller plugin
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

namespace Console\Test\Mvc\Controller\Plugin;

/**
 * Tests for GetOrder controller plugin
 */
class GetOrderTest extends \Library\Test\Mvc\Controller\Plugin\AbstractTest
{
    /**
     * Invoke plugin and test various combinations of request and plugin parameters
     */
    public function testInvoke()
    {
        $plugin = $this->getPlugin();
        $request = $this->_controller->getRequest();
        $parameters = new \Laminas\Stdlib\Parameters();

        // Defaults only
        $parameters->fromArray(array());
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Default', 'direction' => 'asc'),
            $plugin('Default')
        );

        // Explicit order in request
        $parameters->fromArray(array('order' => 'Order'));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Order', 'direction' => 'asc'),
            $plugin('Default')
        );

        // Invalid (empty) order in request
        $parameters->fromArray(array('order' => ''));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Default', 'direction' => 'asc'),
            $plugin('Default')
        );

        // Explicit order and direction in request
        $parameters->fromArray(array('order' => 'Order', 'direction' => 'asc'));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Order', 'direction' => 'asc'),
            $plugin('Default')
        );

        // Explicit order and non-default direction in request
        $parameters->fromArray(array('order' => 'Order', 'direction' => 'desc'));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Order', 'direction' => 'desc'),
            $plugin('Default')
        );

        // Explicit order and invalid direction in request
        $parameters->fromArray(array('order' => 'Order', 'direction' => 'invalid'));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Order', 'direction' => 'asc'),
            $plugin('Default')
        );

        // Explicit order and invalid direction in request, nonstandard default direction
        $parameters->fromArray(array('order' => 'Order', 'direction' => 'invalid'));
        $request->setQuery($parameters);
        $this->assertEquals(
            array('order' => 'Order', 'direction' => 'desc'),
            $plugin('Default', 'desc')
        );
    }
}
