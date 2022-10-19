<?php

/**
 * Subnet definition and properties
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

namespace Model\Network;

use DomainException;
use Library\Validator\IpNetworkAddress;
use ReturnTypeWillChange;

/**
 * Subnet definition and properties
 *
 * This class provides an interface to all subnets and general statistics about
 * details about inventoried, categorized and unknown interfaces.
 *
 * @property string $Address IPv4/IPv6 network address, example: 192.168.1.0 or fe80::6860:a9a6:618a:8ecd
 * @property string $Mask IPv4IPv6 subnet mask, example: 255.255.255.0 or ffff:ffff:ffff:ffff:0000:0000:0000:0000
 * @property-read string $CidrAddress CIDR address/mask notation, example: 192.168.1.0/24 or fe80::/64
 * @property string $Name Assigned name (NULL if no name has been assigned)
 * @property integer $NumInventoried Number of interfaces belonging to an inventoried client
 * @property integer $NumIdentified Number of uninventoried, but manually identified interfaces
 * @property integer $NumUnknown Number of uninventoried and unidentified interfaces
 */
class Subnet extends \Model\AbstractModel
{
    #[ReturnTypeWillChange]
    public function offsetGet($key)
    {
        if ($key == 'CidrAddress') {
            $address = $this['Address'];
            $mask = $this['Mask'];

            // Validate Address and Mask so that the subsequent code can make
            // assumptions about them.
            $validator = new IpNetworkAddress();
            if (!$validator->isValid("$address/$mask")) {
                $messages = $validator->getMessages();
                throw new DomainException(array_shift($messages));
            }

            if (ctype_digit((string) $mask)) {
                $suffix = $mask;
            } else {
                // Convert IPv4 mask to CIDR suffix.
                $suffix = ip2long($mask);
                if ($suffix != 0) { // Next line would not work on 32 bit systems
                    $suffix = 32 - log(($suffix ^ 0xFFFFFFFF) + 1, 2);
                }
            }

            return "$address/$suffix";
        } else {
            return parent::offsetGet($key);
        }
    }
}
