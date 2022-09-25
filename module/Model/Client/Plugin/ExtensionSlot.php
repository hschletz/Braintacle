<?php

/**
 * ExtensionSlot item plugin
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
 * ExtensionSlot item plugin
 */
class ExtensionSlot extends AddOsColumns
{
    /** {@inheritdoc} */
    public function columns()
    {
        // Hydrator does not provide the names
        $this->_select->columns(
            array(
                'name',
                'description',
                'designation',
                'purpose',
                'status',
            )
        );
    }

    public function order(?string $order, string $direction): void
    {
        if ($order == 'id') {
            $order = 'slots.id';
        } else {
            // Since other Properties may map to different columns depending on
            // agent type, "description" is the only reasonable choice that
            // produces sane results with all agents.
            $order = 'description';
        }
        $this->_select->order(array($order => $direction));
    }
}
