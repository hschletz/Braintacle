<?php
/**
 * Configuration for Console module
 *
 * Copyright (C) 2011-2013 Holger Schletz <holger.schletz@web.de>
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

return array(
    'controllers' => array(
        'invokables' => array(
            'Console\Controller\ZF1' => 'Console\Controller\ZF1Controller',
        ),
    ),
    'router' => array(
        'routes' => array(
            // URL paths that are still handled by the ZF1 application, including the base path itself
            'zf1' => array(
                'type'    => 'regex',
                'options' => array(
                    'regex'    => '/(|accounts|computer|duplicates|error|group|index|licenses|login|network|package|preferences|software)/?.*',
                    'spec' => '',
                    'defaults' => array(
                        'controller' => 'Console\Controller\ZF1',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'doctype' => 'HTML4_STRICT',
        'template_path_stack' => array(
            'console' => __DIR__ . '/view',
        ),
        'default_template_suffix' => 'php',
        'display_exceptions' => true,
        'display_not_found_reason' => true,
        'not_found_template'       => 'error/index',
        'exception_template'       => 'error/index',
    ),
);
