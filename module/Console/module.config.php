<?php
/**
 * Configuration for Console module
 *
 * Copyright (C) 2011-2015 Holger Schletz <holger.schletz@web.de>
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
            'PrintForm' => 'Console\Mvc\Controller\Plugin\PrintForm',
        )
    ),
    'controllers' => array(
        'factories' => array(
            'accounts' => 'Console\Service\AccountsControllerFactory',
            'client' => 'Console\Service\ClientControllerFactory',
            'duplicates' => 'Console\Service\DuplicatesControllerFactory',
            'group' => 'Console\Service\GroupControllerFactory',
            'licenses' => 'Console\Service\LicensesControllerFactory',
            'login' => 'Console\Service\LoginControllerFactory',
            'network' => 'Console\Service\NetworkControllerFactory',
            'package' => 'Console\Service\PackageControllerFactory',
            'preferences' => 'Console\Service\PreferencesControllerFactory',
            'software' => 'Console\Service\SoftwareControllerFactory',
        ),
    ),
    'form_elements' => array(
        'abstract_factories' => array(
            'Console\Form\Service\AccountFactory',
            'Console\Form\Service\PackageFactory',
        ),
        'factories' => array(
            'Console\Form\AddToGroup' => 'Console\Form\Service\AddToGroupFactory',
            'Console\Form\CustomFields' => 'Console\Form\Service\CustomFieldsFactory',
            'Console\Form\DefineFields' => 'Console\Form\Service\DefineFieldsFactory',
            'Console\Form\DeleteComputer' => 'Console\Form\Service\DeleteComputerFactory',
            'Console\Form\ManageRegistryValues' => 'Console\Form\Service\ManageRegistryValuesFactory',
            'Console\Form\NetworkDevice' => 'Console\Form\Service\NetworkDeviceFactory',
            'Console\Form\NetworkDeviceTypes' => 'Console\Form\Service\NetworkDeviceTypesFactory',
            'Console\Form\Search' => 'Console\Form\Service\SearchFactory',
            'Console\Form\ShowDuplicates' => 'Console\Form\Service\ShowDuplicatesFactory',
        ),
        'invokables' => array(
            'Console\Form\ClientConfig' => 'Console\Form\ClientConfig',
            'Console\Form\GroupMemberships' => 'Console\Form\GroupMemberships',
            'Console\Form\Import' => 'Console\Form\Import',
            'Console\Form\Login' => 'Console\Form\Login',
            'Console\Form\Preferences\Agent' => 'Console\Form\Preferences\Agent',
            'Console\Form\Preferences\Display' => 'Console\Form\Preferences\Display',
            'Console\Form\Preferences\Download' => 'Console\Form\Preferences\Download',
            'Console\Form\Preferences\Filters' => 'Console\Form\Preferences\Filters',
            'Console\Form\Preferences\Groups' => 'Console\Form\Preferences\Groups',
            'Console\Form\Preferences\Inventory' => 'Console\Form\Preferences\Inventory',
            'Console\Form\Preferences\NetworkScanning' => 'Console\Form\Preferences\NetworkScanning',
            'Console\Form\Preferences\Packages' => 'Console\Form\Preferences\Packages',
            'Console\Form\Preferences\RawData' => 'Console\Form\Preferences\RawData',
            'Console\Form\Preferences\System' => 'Console\Form\Preferences\System',
            'Console\Form\ProductKey' => 'Console\Form\ProductKey',
            'Console\Form\SoftwareFilter' => 'Console\Form\SoftwareFilter',
            'Console\Form\Subnet' => 'Console\Form\Subnet',
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
                        'controller' => 'client',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'Console\Navigation\ClientMenu' => 'Console\Navigation\ClientMenuFactory',
            'Console\Navigation\GroupMenu' => 'Console\Navigation\GroupMenuFactory',
            'Console\Navigation\MainMenu' => 'Console\Navigation\MainMenuFactory',
        ),
    ),
    'view_helpers' => array(
        'factories' => array(
            'consoleUrl' => 'Console\View\Helper\Service\ConsoleUrlFactory',
            'formatMessages' => 'Console\View\Helper\Service\FormatMessagesFactory',
            'table' => 'Console\View\Helper\Service\TableFactory',
        ),
        'invokables' => array(
            'filterDescription' => 'Console\View\Helper\FilterDescription',
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
