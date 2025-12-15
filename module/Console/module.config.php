<?php

/**
 * Configuration for Console module
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

use Console\Mvc\Controller\Plugin\Service\TranslateFactory;
use Console\Mvc\Controller\Plugin\Translate;
use Console\View\Helper\ClientHeader;
use Console\View\Helper\Service\ClientHeaderFactory;
use Laminas\Form\View\Helper\FormElementErrors;
use Laminas\Mvc\Plugin\FlashMessenger\View\Helper\FlashMessenger;
use Psr\Container\ContainerInterface;

return array(
    'controller_plugins' => array(
        'aliases' => array(
            '_' => Translate::class,
            'GetOrder' => 'Console\Mvc\Controller\Plugin\GetOrder',
            'getOrder' => 'Console\Mvc\Controller\Plugin\GetOrder',
            'PrintForm' => 'Console\Mvc\Controller\Plugin\PrintForm',
            'printForm' => 'Console\Mvc\Controller\Plugin\PrintForm',
            'Translate' => Translate::class,
            'translate' => Translate::class,
        ),
        'factories' => array(
            'Console\Mvc\Controller\Plugin\GetOrder' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Mvc\Controller\Plugin\PrintForm' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            Translate::class => TranslateFactory::class,
        ),
    ),
    'controllers' => array(
        'factories' => array(
            'accounts' => 'Console\Service\AccountsControllerFactory',
            'client' => 'Console\Service\ClientControllerFactory',
            'licenses' => 'Console\Service\LicensesControllerFactory',
            'network' => 'Console\Service\NetworkControllerFactory',
            'package' => 'Console\Service\PackageControllerFactory',
            'preferences' => 'Console\Service\PreferencesControllerFactory',
        ),
    ),
    'form_elements' => array(
        'abstract_factories' => array(
            'Console\Form\Service\AccountFactory',
            'Console\Form\Service\PackageFactory',
        ),
        'factories' => array(
            'Console\Form\CustomFields' => 'Console\Form\Service\CustomFieldsFactory',
            'Console\Form\DefineFields' => 'Console\Form\Service\DefineFieldsFactory',
            'Console\Form\DeleteClient' => 'Console\Form\Service\DeleteClientFactory',
            'Console\Form\ManageRegistryValues' => 'Console\Form\Service\ManageRegistryValuesFactory',
            'Console\Form\NetworkDevice' => 'Console\Form\Service\NetworkDeviceFactory',
            'Console\Form\NetworkDeviceTypes' => 'Console\Form\Service\NetworkDeviceTypesFactory',
            'Console\Form\Preferences\Agent' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Display' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Download' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Filters' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Groups' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Inventory' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\NetworkScanning' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\Packages' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\RawData' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\Form\Preferences\System' => 'Laminas\ServiceManager\Factory\InvokableFactory',
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
    'view_helpers' => array(
        'aliases' => array(
            'clientHeader' => ClientHeader::class,
            'consoleUrl' => 'Console\View\Helper\ConsoleUrl',
            'filterDescription' => 'Console\View\Helper\FilterDescription',
            'table' => 'Console\View\Helper\Table',
            'consoleForm' => 'Console\View\Helper\Form\Form',
            'consoleFormFieldset' => 'Console\View\Helper\Form\Fieldset',
            'consoleFormManageRegistryValues' => 'Console\View\Helper\Form\ManageRegistryValues',
            'consoleFormSoftware' => 'Console\View\Helper\Form\Software',
        ),
        'factories' => array(
            ClientHeader::class => ClientHeaderFactory::class,
            'Console\View\Helper\ConsoleUrl' => 'Console\View\Helper\Service\ConsoleUrlFactory',
            'Console\View\Helper\FilterDescription' => 'Console\View\Helper\Service\FilterDescriptionFactory',
            'Console\View\Helper\Table' => 'Console\View\Helper\Service\TableFactory',
            'Console\View\Helper\Form\Form' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\Fieldset' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\ManageRegistryValues' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\ShowDuplicates' => 'Laminas\ServiceManager\Factory\InvokableFactory',
            'Console\View\Helper\Form\Software' => 'Laminas\ServiceManager\Factory\InvokableFactory',
        ),
        'delegators' => [
            FlashMessenger::class => [function (
                ContainerInterface $container,
                $name,
                callable $callback,
            ) {
                // Disable translations in the FlashMessenger view helper.
                // Messages are already translated in controllers to enable
                // string formatting with placeholders.
                /** @var FlashMessenger */
                $helper = $callback();
                $helper->setTranslatorEnabled(false);

                return $helper;
            }],
            FormElementErrors::class => [function (
                ContainerInterface $container,
                $name,
                callable $callback,
            ) {
                // Disable translations in the FormElementErrors view helper.
                // Otherwise it would try to translate the already translated
                // messages from the validators. Translation must be left to the
                // validators because placeholders in message templates can only
                // be handled there.
                /** @var FormElementErrors */
                $helper = $callback();
                $helper->setTranslateMessages(false);

                return $helper;
            }],
        ],
    ),
    'view_manager' => array(
        'doctype' => 'HTML5',
        'template_path_stack' => array(
            'console' => __DIR__ . '/views',
        ),
        'default_template_suffix' => 'php',
    ),
);
