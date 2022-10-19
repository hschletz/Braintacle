<?php

/**
 * Base class for all models.
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

namespace Model;

use ReturnTypeWillChange;

/**
 * Legacy ArrayAccess base class for models.
 *
 * Properties are available as $foo['Bar'] (deprecated) or $foo->bar. They can
 * either be dynamically created (deprecated) or explicitly be declared in a
 * subclass.
 *
 * This helps transition from array access to real object properties. When all
 * instances of array access have been removed, the base class can be removed
 * from the model class.
 *
 * @deprecated Declare properties explicitly and avoid ArrayAccess usage.
 */
abstract class AbstractModel extends \ArrayObject
{
    public function __construct($input = [])
    {
        parent::__construct($input);
    }

    public function __set(string $name, $value): void
    {
        $this->offsetSet(ucfirst($name), $value);
    }

    public function __get(string $name)
    {
        return $this->offsetGet(ucfirst($name));
    }

    public function __isset($name): bool
    {
        return $this->offsetExists(ucfirst($name));
    }

    public function offsetExists($key): bool
    {
        return property_exists($this, lcfirst($key)) || parent::offsetExists($key);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($key)
    {
        $lcKey = lcfirst($key);
        return property_exists($this, $lcKey) ? $this->$lcKey : parent::offsetGet($key);
    }

    public function offsetSet($key, $value): void
    {
        $lcKey = lcfirst($key);
        if (property_exists($this, $lcKey)) {
            $this->$lcKey = $value;
        } else {
            parent::offsetSet($key, $value);
        }
    }
}
