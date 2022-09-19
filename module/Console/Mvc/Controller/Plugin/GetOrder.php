<?php

/**
 * Extract and validate "order" and "direction" URL parameters
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

namespace Console\Mvc\Controller\Plugin;

/**
 * Extract and validate "order" and "direction" URL parameters
 */
class GetOrder extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Extract and validate "order" and "direction" URL parameters
     *
     * @param string $defaultOrder default for missing/invalid "order"
     * @param string $defaultDirection default for missing/invalid "direction"
     * @return array ['order' => $order, 'direction => '$direction]
     */
    public function __invoke($defaultOrder, $defaultDirection = 'asc')
    {
        $request = $this->getController()->getRequest();

        $order = $request->getQuery('order');
        if (empty($order)) {
            $order = $defaultOrder;
        }
        $direction = $request->getQuery('direction');
        if ($direction != 'asc' and $direction != 'desc') {
            $direction = $defaultDirection;
        }

        return array('order' => $order, 'direction' => $direction);
    }
}
