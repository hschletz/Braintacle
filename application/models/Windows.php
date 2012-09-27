<?php
/**
 * Class representing Windows-specific information for a computer
 *
 * $Id$
 *
 * Copyright (C) 2011,2012 Holger Schletz <holger.schletz@web.de>
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
 *
 * @package Models
 */
/**
 * Windows-specific information for a computer
 *
 * Properties:
 *
 * - <b>UserDomain:</b> Domain of logged in user (for local accounts this is identical to the computer name)
 * - <b>Company:</b> Company name (typed in at installation)
 * - <b>Owner:</b> Owner (typed in at installation)
 * - <b>ProductKey:</b> Product Key (aka license key, typed in at installation)
 * - <b>ProductId:</b> Product ID (installation-specific, may or may not be unique)
 * @package Models
 */
class Model_Windows extends Model_Abstract
{

    /** {@inheritdoc} */
    protected $_propertyMap = array(
        // Values from 'hardware' table
        'UserDomain' => 'userdomain',
        'Company' => 'wincompany',
        'Owner' => 'winowner',
        'ProductKey' => 'winprodkey',
        'ProductId' => 'winprodid',
    );

    /**
     * Create Model_Windows object for given computer
     *
     * It is valid to pass a non-Windows computer object in which case the
     * content of the returned object is undefined.
     * @param Model_Computer $computer
     * @return Model_Windows
     * @throws UnexpectedValueException if $computer is invalid
     **/
    public static function getWindows($computer)
    {
        $windows = Model_Database::getAdapter()
                   ->select()
                   ->from('hardware', array('userdomain, wincompany, winowner, winprodkey, winprodid'))
                   ->where('id = ?', $computer->getId())
                   ->query()
                   ->fetchObject(__CLASS__);
        if (!$windows) {
            throw new UnexpectedValueException('Invalid computer ID: ' . $computer->getId);
        }
        return $windows;
    }

}
