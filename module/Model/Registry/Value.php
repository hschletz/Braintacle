<?php

/**
 * Registry value definition
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

namespace Model\Registry;

use ReturnTypeWillChange;

/**
 * Registry value definition
 *
 * This is the definition of a registry value (in registry terminology). It
 * defines registry data to be collected by agents, and also represents the base
 * value of inventored registry data.
 *
 * @property integer $Id ID
 * @property string $Name Userdefined display name
 * @property integer $RootKey Root key, one of the HKEY_* constants
 * @property string $SubKeys Path to the key that contains the value
 * @property string $Value Registry value to inventory (NULL for all values)
 * @property-read string $FullPath Textual representation of configured value
 */
class Value extends \Model\AbstractModel
{
    /**
     * Root key HKEY_CLASSES_ROOT
     **/
    const HKEY_CLASSES_ROOT = 0;

    /**
     * Root key HKEY_CURRENT_USER
     **/
    const HKEY_CURRENT_USER = 1;

    /**
     * Root key HKEY_LOCAL_MACHINE
     **/
    const HKEY_LOCAL_MACHINE = 2;

    /**
     * Root key HKEY_USERS
     **/
    const HKEY_USERS = 3;

    /**
     * Root key HKEY_CURRENT_CONFIG
     **/
    const HKEY_CURRENT_CONFIG = 4;

    /**
     * Root key HKEY_DYN_DATA
     **/
    const HKEY_DYN_DATA = 5;

    /**
     * Textual representations of root keys, in the order used by the Windows
     * registry editor
     * @var string[]
     **/
    protected static $_rootKeys = array(
        self::HKEY_CLASSES_ROOT => 'HKEY_CLASSES_ROOT',
        self::HKEY_CURRENT_USER => 'HKEY_CURRENT_USER',
        self::HKEY_LOCAL_MACHINE => 'HKEY_LOCAL_MACHINE',
        self::HKEY_USERS => 'HKEY_USERS',
        self::HKEY_CURRENT_CONFIG => 'HKEY_CURRENT_CONFIG',
        self::HKEY_DYN_DATA => 'HKEY_DYN_DATA',
    );

    /**
     * Retrieve textual representations of root keys
     *
     * The keys of the returned array are the HKEY_* constants. The ordering is
     * the same as in the Windows registry editor.
     * @return string[]
     **/
    public static function rootKeys()
    {
        return self::$_rootKeys;
    }

    #[ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($key == 'FullPath') {
            $value  = self::$_rootKeys[$this['RootKey']];
            $value .= '\\';
            $value .= $this['SubKeys'];
            $value .= '\\';
            $value .= $this['Value'] ?: '*';
        } else {
            $value = parent::offsetGet($key);
        }
        return $value;
    }
}
