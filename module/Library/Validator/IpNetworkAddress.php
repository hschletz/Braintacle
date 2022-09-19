<?php

/**
 * Validate IP Network Address
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

namespace Library\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Ip;

/**
 * Validate IP Network Address (address/mask)
 *
 * Accepts IPv4 and IPv6 addresses. For IPv4, mask can be either numeric (0-32)
 * or dotted-quad (a.b.c.d). For IPv6, only numeric suffixes (0-128) are
 * allowed.
 */
class IpNetworkAddress extends AbstractValidator
{
    const FORMAT = 'format';
    const ADDRESS = 'address';
    const MASK = 'mask';

    protected $messageTemplates = array(
        self::FORMAT => "'%value%' is not a valid network address",
        self::ADDRESS => "'%value%' does not contain a valid IP address",
        self::MASK => "'%value%' does not contain a valid mask",
    );

    public function isValid($value)
    {
        $this->setValue($value);

        $parts = explode('/', $value);
        if (count($parts) != 2) {
            $this->error(self::FORMAT);
            return false;
        }
        [$address, $mask] = $parts;

        $ipValidator = new Ip([
            'allowipv4' => true,
            'allowipv6' => false,
            'allowipvfuture' => false,
            'allowliteral' => false,
        ]);
        if ($ipValidator->isValid($address)) {
            // IPv4 address
            if (ctype_digit((string) $mask) and $mask >= 0 and $mask <= 32) {
                return true;
            }
            if ($ipValidator->isValid($mask)) {
                // IPv4 mask in dotted quad notation. Only masks starting with
                // consecutive 1 bits are considered valid.
                $suffix = ip2long($mask);
                if ($suffix != 0) { // Next line would not work on 32 bit systems
                    $suffix = 32 - log(($suffix ^ 0xFFFFFFFF) + 1, 2);
                }
                if (ctype_digit((string) $suffix)) {
                    return true;
                }
            }

            $this->error(self::MASK);
            return false;
        } else {
            $ipValidator->setOptions(['allowipv4' => false, 'allowipv6' => true]);
            if (!$ipValidator->isValid($address)) {
                $this->error(self::ADDRESS);
                return false;
            }
            // IPv6 address. Mask should already be a numeric suffix.
            if (!ctype_digit((string) $mask) or $mask < 0 or $mask > 128) {
                $this->error(self::MASK);
                return false;
            }
            return true;
        }
    }
}
