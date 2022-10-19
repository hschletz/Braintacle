<?php

/**
 * Hydrator for extension slots
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

namespace Database\Hydrator;

use Model\AbstractModel;

/**
 * Hydrator for extension slots
 *
 * Sanitizes incompatible structures produced by different agents.
 */
class ExtensionSlots implements \Laminas\Hydrator\HydratorInterface
{
    /** {@inheritdoc} */
    public function hydrate(array $data, $object)
    {
        if ($data['is_windows']) {
            $object->Name = $data['designation'];
            $object->Status = ($data['purpose'] ?: $data['status']);
        } else {
            $object->Name = $data['name'];
            $object->Status = $data['status'];
            $object->SlotId = $data['designation'];
        }
        $object->Description = $data['description'];
        return $object;
    }

    /** {@inheritdoc} */
    public function extract(object $object): array
    {
        $data = [];
        $data['name'] = $object->name;
        $data['description'] = $object->description;
        if ($object instanceof AbstractModel && $object->offsetExists('SlotId') || property_exists($object, 'slotId')) {
            $data['designation'] = $object->slotId;
            $data['purpose'] = null;
            $data['status'] = $object->status;
        } else {
            $data['designation'] = $object->name;
            $data['purpose'] = $object->status;
            $data['status'] = null;
        }
        return $data;
    }
}
