<?php

/**
 * Mark string as translatable
 *
 * Copyright (C) 2011-2021 Holger Schletz <holger.schletz@web.de>
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

namespace Library\Mvc\Controller\Plugin;

/**
 * Mark string as translatable
 *
 * This is a dummy plugin that can be used to mark strings translatable. It does
 * not do anything (it returns the string unchanged), but allows xgettext to
 * detect and extract translatable strings.
 *
 * Example:
 *
 *     $this->_('translatable string')
 */
class TranslationHelper extends \Laminas\Mvc\Controller\Plugin\AbstractPlugin
{
    /**
     * Translation helper
     *
     * @param string $string Translatable string
     * @return string same as $string
     */
    public function __invoke($string)
    {
        return $string;
    }
}
