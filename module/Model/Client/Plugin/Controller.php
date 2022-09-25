<?php

/**
 * Controller item plugin
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

namespace Model\Client\Plugin;

/**
 * Controller item plugin
 */
class Controller extends AddOsColumns
{
    /** {@inheritdoc} */
    public function columns()
    {
        // Hydrator does not provide the names
        $this->_select->columns(
            array(
                'type',
                'name',
                'manufacturer',
                'description',
                'version',
            )
        );
    }

    public function order(?string $order, string $direction): void
    {
        if ($order == 'id') {
            $order = 'controllers.id';
        } else {
            // All properties may map to different columns depending on agent
            // type. "manufacturer" is the only column that contains almost the
            // same information for all agents.
            $order = 'manufacturer';
        }
        $this->_select->order(array($order => $direction));
    }
}
