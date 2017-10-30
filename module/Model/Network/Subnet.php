<?php
/**
 * Subnet definition and properties
 *
 * Copyright (C) 2011-2017 Holger Schletz <holger.schletz@web.de>
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
class Subnet extends \ArrayObject
{
    /** {@inheritdoc} */
    public function offsetGet($index)
    {
        if ($index == 'CidrAddress') {
            $mask = $this['Mask'];
            $validator = new \Zend\Validator\Ip(
                array(
                    'allowipv4' => true,
                    'allowipv6' => true,
                    'allowipvfuture' => false,
                    'allowliteral' => false,
                )
            );
            if (!$validator->isValid($mask)) {
                throw new \DomainException('Not an IP address mask: ' . $mask);
            }
            $validator->setOptions(array('allowipv6' => false));
            if ($validator->isValid($mask)) {
                // IPv4 address
                $mask = ip2long($this['Mask']);
                if ($mask != 0) { // Next line would not work on 32 bit systems
                    $mask = 32 - log(($mask ^ 0xFFFFFFFF) + 1, 2);
                }
                $mask = (string) $mask;
                if (!ctype_digit($mask)) {
                    throw new \DomainException('Not a CIDR mask: ' . $this['Mask']);
                }
                return $this['Address'] . '/' . $mask;
            } else {
                // IPv6 address. Since 128 bit integers are difficult to handle,
                // parse mask as hex strings. Assume unabbreviated notation (32
                // hex digits, strip ':' first). Mask should start with zero or
                // more F digits, followed by E, C or 8 if the boundary is not
                // between full hex digits, followed by zero or more 0 digits.
                $mask = str_replace(':', '', $mask);
                if (strlen($mask) != 32 or !preg_match('/^(F*)([8CE]?)0*$/i', $mask, $matches)) {
                     throw new \DomainException('Not a CIDR mask: ' . $this['Mask']);
                }
                $length = strlen($matches[1]) * 4; // 4 bits per F digit
                if ($matches[2]) {
                    // Not on a 4 bit boundary. Calculate additional bits.
                    $length += 4 - log((hexdec($matches[2]) ^ 0xF) + 1, 2);
                }
                return $this['Address'] . '/' . $length;
            }
        } else {
            return parent::offsetGet($index);
        }
    }
}
