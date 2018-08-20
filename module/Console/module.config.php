<?php
/**
 * Configuration for Console module
 *
 * Copyright (C) 2011-2018 Holger Schletz <holger.schletz@web.de>
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
        'aliases' => array(
            'GetOrder' => 'Console\Mvc\Controller\Plugin\GetOrder',
            'getOrder' => 'Console\Mvc\Controller\Plugin\GetOrder',
            'PrintForm' => 'Console\Mvc\Controller\Plugin\PrintForm',
            'printForm' => 'Console\Mvc\Controller\Plugin\PrintForm',
            'SetActiveMenu' => 'Console\Mvc\Controller\Plugin\SetActiveMenu',
            'setActiveMenu' => 'Console\Mvc\Controller\Plugin\SetActiveMenu',
        ),
        'factories' => array(
            'Console\Mvc\Controller\Plugin\GetOrder' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Mvc\Controller\Plugin\SetActiveMenu' =>
                'Console\Mvc\Controller\Plugin\Service\SetActiveMenuFactory',
            'Console\Mvc\Controller\Plugin\PrintForm' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ),
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
            'Console\Form\ClientConfig' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\CustomFields' => 'Console\Form\Service\CustomFieldsFactory',
            'Console\Form\DefineFields' => 'Console\Form\Service\DefineFieldsFactory',
            'Console\Form\DeleteClient' => 'Console\Form\Service\DeleteClientFactory',
            'Console\Form\GroupMemberships' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Import' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Login' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\ManageRegistryValues' => 'Console\Form\Service\ManageRegistryValuesFactory',
            'Console\Form\NetworkDevice' => 'Console\Form\Service\NetworkDeviceFactory',
            'Console\Form\NetworkDeviceTypes' => 'Console\Form\Service\NetworkDeviceTypesFactory',
            'Console\Form\Preferences\Agent' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Display' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Download' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Filters' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Groups' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Inventory' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\NetworkScanning' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Packages' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\RawData' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\System' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\ProductKey' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Search' => 'Console\Form\Service\SearchFactory',
            'Console\Form\ShowDuplicates' => 'Console\Form\Service\ShowDuplicatesFactory',
            'Console\Form\SoftwareFilter' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Subnet' => 'Zend\ServiceManager\Factory\InvokableFactory',
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
    'translator' => array(
        'translation_file_patterns' => array(
            array(
                'type' => 'Po',
                'base_dir' => __DIR__ . '/data/i18n',
                'pattern' => '%s.po',
            ),
        ),
    ),
    'view_helpers' => array(
        'aliases' => array(
            'ConsoleUrl' => 'Console\View\Helper\ConsoleUrl',
            'consoleUrl' => 'Console\View\Helper\ConsoleUrl',
            'FilterDescription' => 'Console\View\Helper\FilterDescription',
            'filterDescription' => 'Console\View\Helper\FilterDescription',
            'FormatMessages' => 'Console\View\Helper\FormatMessages',
            'formatMessages' => 'Console\View\Helper\FormatMessages',
            'Table' => 'Console\View\Helper\Table',
            'table' => 'Console\View\Helper\Table',
            'consoleForm' => 'Console\View\Helper\Form\Form',
            'consoleFormFieldset' => 'Console\View\Helper\Form\Fieldset',
            'consoleFormClientConfig' => 'Console\View\Helper\Form\ClientConfig',
            'consoleFormManageRegistryValues' => 'Console\View\Helper\Form\ManageRegistryValues',
        ),
        'factories' => array(
            'Console\View\Helper\ConsoleUrl' => 'Console\View\Helper\Service\ConsoleUrlFactory',
            'Console\View\Helper\FilterDescription' => 'Console\View\Helper\Service\FilterDescriptionFactory',
            'Console\View\Helper\FormatMessages' => 'Console\View\Helper\Service\FormatMessagesFactory',
            'Console\View\Helper\Table' => 'Console\View\Helper\Service\TableFactory',
            'Console\View\Helper\Form\Form' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\Fieldset' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\ClientConfig' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\ManageRegistryValues' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ),
    ),
    'view_manager' => array(
        'doctype' => 'HTML5',
        'template_path_stack' => array(
            'console' => __DIR__ . '/views',
        ),
        'default_template_suffix' => 'php',
        'display_exceptions' => true,
        'display_not_found_reason' => true,
        'not_found_template'       => 'error/index',
        'exception_template'       => 'error/index',
    ),
);
