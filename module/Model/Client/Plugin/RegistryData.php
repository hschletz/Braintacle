<?php

/**
 * RegistryData item plugin
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

namespace Model\Client\Plugin;

/**
 * RegistryData item plugin
 *
 * @psalm-suppress UnusedClass
 */
class RegistryData extends DefaultPlugin
{
    public function order(?string $order, string $direction): void
    {
        parent::order($order, $direction);
        if (key($this->_select->getRawState(\Laminas\Db\Sql\Select::ORDER)) == 'registry.name') {
            // Since there can be multiple instances of Value, provide secondary
            // ordering by data
            $this->_select->order(array('registry.regvalue' => $direction));
        }
    }
}
