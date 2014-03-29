<?php
/**
 * Configuration for Console module
 *
 * Copyright (C) 2011-2014 Holger Schletz <holger.schletz@web.de>
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
    'controller_plugins' => array(
        'factories' => array(
            'SetActiveMenu' => 'Console\Mvc\Controller\Plugin\Service\SetActiveMenuFactory',
        ),
        'invokables' => array(
            'GetOrder' => 'Console\Mvc\Controller\Plugin\GetOrder',
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'Console\Controller\ZF1' => 'Console\Controller\ZF1Controller',
        ),
        'factories' => array(
            'accounts' => 'Console\Service\AccountsControllerFactory',
            'duplicates' => 'Console\Service\DuplicatesControllerFactory',
            'licenses' => 'Console\Service\LicensesControllerFactory',
            'login' => 'Console\Service\LoginControllerFactory',
            'network' => 'Console\Service\NetworkControllerFactory',
            'preferences' => 'Console\Service\PreferencesControllerFactory',
            'software' => 'Console\Service\SoftwareControllerFactory',
        ),
    ),
    'form_elements' => array(
        'factories' => array(
            'Console\Form\ManageRegistryValues' => 'Console\Form\Service\ManageRegistryValuesFactory',
            'Console\Form\ShowDuplicates' => 'Console\Form\Service\ShowDuplicatesFactory',
        ),
    ),
    'router' => array(
        'routes' => array(
            'console' => array(
                'type' => 'segment',
                'options' => array(
                    // Match "console" prefix, followed by controller and action
                    // names. All three components are optional except the
                    // controller, which is required if an action is given.
                    // Matches with or without trailing slash.
                    // Note: a controller cannot be named "console".
                    'route' => '/[console[/]][:controller[/][:action[/]]]',
                    'defaults' => array(
                        'controller' => 'login', // TODO: default to "computer" when available
                        'action' => 'index',
                    ),
                ),
            ),
            // URL paths that are still handled by the ZF1 application
            'zf1' => array(
                'type'    => 'regex',
                'options' => array(
                    'regex'    => '/(console/)?(computer|error|group|index|package)/?.*',
                    'spec' => '',
                    'defaults' => array(
                        'controller' => 'Console\Controller\ZF1',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Console\Navigation\MainMenu' => 'Console\Navigation\MainMenuFactory',
            'Console\Form\Account\Edit' => 'Console\Form\Service\Account\EditFactory',
        ),
        'invokables' => array(
            'Console\Form\Account\New' => 'Form_Account_New',
            'Console\Form\DefineFields' => 'Form_DefineFields',
            'Console\Form\NetworkDeviceTypes' => 'Form_ManageNetworkDeviceTypes',
            'Console\Form\Login' => 'Form_Login',
            'Console\Form\NetworkDevice' => 'Form_NetworkDevice',
            'Console\Form\Preferences\Agent' => 'Form_Preferences_Agent',
            'Console\Form\Preferences\Display' => 'Form_Preferences_Display',
            'Console\Form\Preferences\Download' => 'Form_Preferences_Download',
            'Console\Form\Preferences\Filters' => 'Form_Preferences_Filters',
            'Console\Form\Preferences\Groups' => 'Form_Preferences_Groups',
            'Console\Form\Preferences\Inventory' => 'Form_Preferences_Inventory',
            'Console\Form\Preferences\NetworkScanning' => 'Form_Preferences_NetworkScanning',
            'Console\Form\Preferences\Packages' => 'Form_Preferences_Packages',
            'Console\Form\Preferences\RawData' => 'Form_Preferences_RawData',
            'Console\Form\Preferences\System' => 'Form_Preferences_System',
            'Console\Form\SoftwareFilter' => 'Form_SoftwareFilter',
            'Console\Form\Subnet' => 'Form_Subnet',
        )
    ),
    'view_helpers' => array(
        'factories' => array(
            'consoleUrl' => 'Console\View\Helper\Service\ConsoleUrlFactory',
            'table' => 'Console\View\Helper\Service\TableFactory',
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
