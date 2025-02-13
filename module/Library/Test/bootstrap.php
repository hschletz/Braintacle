<?php

/**
 * Bootstrap for unit tests
 *
 * Copyright (C) 2011-2025 Holger Schletz <holger.schletz@web.de>
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

namespace Library;

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

error_reporting(-1);
date_default_timezone_set('Europe/Berlin');
\Locale::setDefault('de');

/**
 * A minimal stream wrapper to simulate I/O errors
 *
 * No stream methods are implemented except stream_open() (so that a stream can
 * be opened) and stream_eof() (which always returns FALSE to distinct a read
 * error from a normal EOF). Every other method will cause the calling stream
 * function to fail, allowing testing the error handling in the application.
 */
class StreamWrapperFail
{
    public function stream_open()
    {
        return true;
    }

    public function stream_eof()
    {
        return false;
    }
}
stream_wrapper_register('fail', 'Library\StreamWrapperFail');
