<?php
/**
 * Subnet definition and properties
 *
 * Copyright (C) 2011-2016 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Network;

/**
 * Subnet definition and properties
 *
 * This class provides an interface to all subnets and general statistics about
 * details about inventoried, categorized and unknown interfaces.
 *
 * @property string $Address IPv4 Network Address, example: 192.168.1.0
 * @property string $Mask IPv4 Subnet Mask, example: 255.255.255.0
 * @property-read string $CidrAddress CIDR Address/Mask notation, example: 192.168.1.0/24
 * @property string $Name Assigned name (NULL if no name has been assigned)
 * @property integer $NumInventoried Number of interfaces belonging to an inventoried client
 * @property integer $NumIdentified Number of uninventoried, but manually identified interfaces
 * @property integer $NumUnknown Number of uninventoried and unidentified interfaces
 */
class Subnet extends \ArrayObject
{
    /** {@inheritdoc} */
    public function offsetGet($index)
    {
        if ($index == 'CidrAddress') {
            $mask = ip2long($this['Mask']);
            if ($mask != 0) { // Next line would not work on 32 bit systems
                $mask = 32 - log(($mask ^ 0xffffffff) + 1, 2);
            }
            $mask = (string) $mask;
            if (!ctype_digit($mask)) {
                throw new \DomainException('Not a CIDR mask: ' . $this['Mask']);
            }
            return $this['Address'] . '/' . $mask;
        } else {
            return parent::offsetGet($index);
        }
    }
}
