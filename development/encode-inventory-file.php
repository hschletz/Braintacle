#!/usr/bin/php
<?php

/**
 * Encode a file as compressed inventory data
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

/**
 * USAGE: encode-inventory-file.php <input file> <output file>
 *
 * Agents may generate inventory data as a zlib stream. The file format is
 * rather impractical; this tool is mostly useful to generate input for testing
 * the decoder.
 */

error_reporting(E_ALL);

if ($_SERVER['argc'] != 3) {
    print "USAGE: encode-inventory-file.php <input file> <output file>\n";
    exit(1);
}

$input = file_get_contents($_SERVER['argv'][1]);
if (!$input) {
    print "Could not read input file\n";
    exit(1);
}

$context = deflate_init(ZLIB_ENCODING_DEFLATE);
if (!$context) {
    print "Could not create deflate context\n";
    exit(1);
}

// Compress input in blocks of 32 kB with ZLIB_SYNC_FLUSH for each block, just
// like the agent does.
$output = '';
foreach (str_split($input, 0x8000) as $chunk) {
    $output .= deflate_add($context, $chunk, ZLIB_SYNC_FLUSH);
}

if (file_put_contents($_SERVER['argv'][2], $output) !== strlen($output)) {
    print "Could not write output file\n";
    exit(1);
}
