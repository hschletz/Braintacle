<?php

/**
 * Validate string to be a path to a writable directory
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

/**
 * Validate string to be a path to a writable directory
 */
class DirectoryWritable extends \Laminas\Validator\AbstractValidator
{
    /**
     * Key for message template: not a directory or inaccessible
     */
    const DIRECTORY = 'directory';

    /**
     * Key for message template: directory not writable
     */
    const WRITABLE = 'writable';

    /**
     * Validation failure message template definitions
     * @var string[]
     */
    protected $messageTemplates = array(
        self::DIRECTORY => "'%value%' is not a directory or inaccessible",
        self::WRITABLE => "Directory '%value%' is not writable",
    );

    /**
     * Returns true if $value is a directory and writable
     *
     * @param string $value String to be validated
     * @return bool
     */
    public function isValid($value)
    {
        $this->setValue($value);

        if (is_dir($value)) {
            if (is_writable($value)) {
                return true;
            } else {
                $this->error(self::WRITABLE);
                return false;
            }
        } else {
            $this->error(self::DIRECTORY);
            return false;
        }
    }
}
