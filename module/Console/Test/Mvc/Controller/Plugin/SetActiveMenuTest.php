<?php

/**
 * Tests for SetActiveMenu controller plugin
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
 * Tests for SetActiveMenu controller plugin
 */
class SetActiveMenuTest extends \Library\Test\Mvc\Controller\Plugin\AbstractTest
{
    /**
     * Invoke plugin and test various valid and invalid parameter sets
     */
    public function testInvoke()
    {
        // Navigation structure for tests. It contains the label "test" 3 times
        // (once on a top menu with no submenu, twice on different submenus) to
        // be able to test for correct location of labels.
        $data = array(
            array(
                'label' => 'test',
                'uri' => 'top0',
            ),
            array(
                'label' => 'top1',
                'uri' => 'top1',
                'pages' => array(
                    array(
                        'label' => 'test',
                        'uri' => 'sub1',
                    ),
                ),
            ),
            array(
                'label' => 'top2',
                'uri' => 'top2',
                'pages' => array(
                    array(
                        'label' => 'test',
                        'uri' => 'sub2',
                    ),
                ),
            ),
        );

        // Test first top menu
        $navigation = new \Laminas\Navigation\Navigation($data);
        $plugin = new \Console\Mvc\Controller\Plugin\SetActiveMenu($navigation);
        $plugin('test');
        $this->assertTrue($navigation->findOneByUri('top0')->isActive());

        // Test last top menu
        $navigation = new \Laminas\Navigation\Navigation($data);
        $plugin = new \Console\Mvc\Controller\Plugin\SetActiveMenu($navigation);
        $plugin('top2');
        $this->assertTrue($navigation->findOneByUri('top2')->isActive());

        // Test both submenus (ensure that the correct one is picked)
        $navigation = new \Laminas\Navigation\Navigation($data);
        $plugin = new \Console\Mvc\Controller\Plugin\SetActiveMenu($navigation);
        $plugin('top1', 'test');
        $this->assertTrue($navigation->findOneByUri('sub1')->isActive());
        $navigation = new \Laminas\Navigation\Navigation($data);
        $plugin = new \Console\Mvc\Controller\Plugin\SetActiveMenu($navigation);
        $plugin('top2', 'test');
        $this->assertTrue($navigation->findOneByUri('sub2')->isActive());

        // Test exception on invalid top menu
        try {
            $plugin('invalid');
            $this->fail('No exception thrown on invalid $mainPage');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid top menu page: invalid', $e->getMessage());
        }

        // Test exception on invalid submenu if top menu does not have a submenu
        try {
            $plugin('test', 'invalid');
            $this->fail('No exception thrown on invalid $subPage');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid submenu page: invalid', $e->getMessage());
        }

        // Test exception on invalid submenu if top menu has a submenu
        try {
            $plugin('top1', 'invalid');
            $this->fail('No exception thrown on invalid $subPage');
        } catch (\Exception $e) {
            $this->assertEquals('Invalid submenu page: invalid', $e->getMessage());
        }
    }
}
