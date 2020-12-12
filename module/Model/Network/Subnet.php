<?php
/**
 * Subnet definition and properties
 *
 * Copyright (C) 2011-2020 Holger Schletz <holger.schletz@web.de>
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
    /** {@inheritdoc} */
    public function offsetGet($index)
    {
        if ($index == 'CidrAddress') {
            $address = $this['Address'];
            $mask = $this['Mask'];
            $validator = new \Zend\Validator\Ip([
                'allowipv4' => true,
                'allowipv6' => false,
                'allowipvfuture' => false,
                'allowliteral' => false,
            ]);
            if ($validator->isValid($address)) {
                // IPv4 address
                if (!$validator->isValid($mask)) {
                    throw new \DomainException('Not an IPv4 address mask: ' . $mask);
                }
                // Convert mask to CIDR suffix.
                $suffix = ip2long($mask);
                if ($suffix != 0) { // Next line would not work on 32 bit systems
                    $suffix = 32 - log(($suffix ^ 0xFFFFFFFF) + 1, 2);
                }
                $suffix = (string) $suffix;
                if (!ctype_digit($suffix)) {
                    throw new \DomainException('Not a CIDR mask: ' . $mask);
                }
            } else {
                $validator->setOptions(['allowipv4' => false, 'allowipv6' => true]);
                if (!$validator->isValid($address)) {
                    throw new \DomainException('Not an IP address: ' . $address);
                }
                // IPv6 address. Mask should already be a valid suffix.
                if (!ctype_digit((string) $mask) or $mask < 0 or $mask > 128) {
                     throw new \DomainException('Not a CIDR mask: ' . $mask);
                }
                $suffix = $mask;
            }
            return "$address/$suffix";
        } else {
            return parent::offsetGet($index);
        }
    }
}
