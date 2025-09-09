<?php

namespace Braintacle\Configuration;

use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Model\Client\Client;
use Model\Config;
use Model\Group\Group;

/**
 * Manage client/group configuration.
 */
final class ClientConfig
{
    private const OptionsWithDefaults = [
        'contactInterval',
        'inventoryInterval',
        'packageDeployment',
        'downloadPeriodDelay',
        'downloadCycleDelay',
        'downloadFragmentDelay',
        'downloadMaxPriority',
        'downloadTimeout',
        'allowScan',
        'scanSnmp',
    ];
    private const BooleanOptions = ['packageDeployment', 'allowScan', 'scanSnmp'];

    public function __construct(private Config $config) {}

    /**
     * Get configured options for client/group.
     *
     * The returned array contains elements for all options. Unconfigured
     * options are set to NULL.
     */
    public function getOptions(Client | Group $object): array
    {
        $options = [];
        foreach (self::OptionsWithDefaults as $option) {
            $value = $object->getConfig($option);
            if (in_array($option, self::BooleanOptions)) {
                // These options can only be disabled ($value is 0) or
                // unconfigured ($value is NULL), in which case the default is
                // effective. Map condition to a boolean value.
                /** @psalm-suppress UnhandledMatchCondition */
                $value = match ($value) {
                    null => true,
                    0 => false,
                };
            }
            $options[$option] = $value;
        }

        if ($object instanceof Client) {
            $options['scanThisNetwork'] = $object->getConfig('scanThisNetwork');
        }

        return $options;
    }

    /**
     * Get default values for client options.
     *
     * Returns the default settings that override or get overriden by the
     * client's setting. They are determined from the global settings and/or all
     * groups of which the client is a member.
     */
    public function getClientDefaults(Client $client): array
    {
        $groups = $client->getGroups();
        foreach (self::OptionsWithDefaults as $option) {
            $groupValues = [];
            foreach ($groups as $group) {
                $groupValue = $group->getConfig($option);
                if ($groupValue !== null) {
                    $groupValues[] = $groupValue;
                }
            }

            $default = null;
            switch ($option) {
                case 'inventoryInterval':
                    $default = $this->config->inventoryInterval;
                    // Special values 0 and -1 always take precedence if
                    // configured globally. Otherwise use smallest value from
                    // groups if defined.
                    if ($groupValues && $default >= 1) {
                        $default = min($groupValues);
                    }
                    break;
                case 'contactInterval':
                case 'downloadMaxPriority':
                case 'downloadTimeout':
                    if ($groupValues) {
                        $default = min($groupValues);
                    }
                    break;
                case 'downloadPeriodDelay':
                case 'downloadCycleDelay':
                case 'downloadFragmentDelay':
                    if ($groupValues) {
                        $default = max($groupValues);
                    }
                    break;
                case 'packageDeployment':
                case 'scanSnmp':
                    // FALSE if disabled globally or in any group, otherwise TRUE.
                    if (in_array(0, $groupValues)) {
                        $default = false;
                    } else {
                        $default = (bool) $this->config->$option;
                    }
                    break;
                case 'allowScan':
                    // FALSE if disabled globally or in any group, otherwise TRUE.
                    if (in_array(0, $groupValues)) {
                        $default = false;
                    } else {
                        $default = (bool) $this->config->scannersPerSubnet;
                    }
                    break;
            }
            if ($default === null) {
                assert($option != 'allowScan'); // $default is definitely set above
                // Fall back to global value
                $default = $this->config->$option;
            }
            $defaults[$option] = $default;
        }

        return $defaults;
    }

    /**
     * Get global default values.
     *
     * Returns the global settings that override or get overriden by group
     * and/or client settings.
     */
    public function getGlobalDefaults(): array
    {
        foreach (self::OptionsWithDefaults as $option) {
            if ($option == 'allowScan') {
                $default = $this->config->scannersPerSubnet;
            } else {
                $default = $this->config->$option;
            }
            if (in_array($option, self::BooleanOptions)) {
                $default = (bool) $default;
            }
            $defaults[$option] = $default;
        }

        return $defaults;
    }

    /**
     * Get effective values for client.
     *
     * Returns an associative array of effective settings. They are determined
     * from the client's individual setting, the global setting and/or all
     * groups of which the client is a member. The exact rules are:
     *
     * - packageDeployment, allowScan and scanSnmp resolve to FALSE if the
     *   setting is disabled either globally, for any group or for the client,
     *   otherwise TRUE.
     * - For inventoryInterval, if the global setting is one of the special
     *   values 0 or -1, this value is returned. Otherwise, return the
     *   smallest value of the group and client setting. If this is undefined,
     *   use global setting.
     * - contactInterval, downloadMaxPriority and downloadTimeout evaluate (in
     *   that order): the client setting, the smallest value of all group
     *   settings and the global setting. The first non-null result is returned.
     * - downloadPeriodDelay, downloadCycleDelay, downloadFragmentDelay evaluate
     *   (in that order): the client setting, the largest value of all group
     *   settings and the global setting. The first non-null result is returned.
     *
     * @return array<string, int|bool|null>
     */
    public function getEffectiveConfig(Client $client): array
    {
        $defaults = $this->getClientDefaults($client);
        foreach (self::OptionsWithDefaults as $option) {
            $default = $defaults[$option];
            if ($option == 'inventoryInterval') {
                $globalValue = $this->config->inventoryInterval;
                if ($globalValue <= 0) {
                    // Special global values 0 and -1 always take precedence.
                    $value = $globalValue;
                } else {
                    // Get smallest value of client and group settings
                    $value = $client->getConfig('inventoryInterval');
                    foreach ($client->getGroups() as $group) {
                        $groupValue = $group->getConfig('inventoryInterval');
                        if ($value === null || ($groupValue !== null && $groupValue < $value)) {
                            $value = $groupValue;
                        }
                    }
                    // Fall back to global default if not set anywhere else
                    if ($value === null) {
                        $value = $globalValue;
                    }
                }
            } elseif (in_array($option, self::BooleanOptions)) {
                // If default is FALSE, return FALSE.
                // Otherwise override default if explicitly disabled.
                if ($default && $client->getConfig($option) === 0) {
                    $value = false;
                } else {
                    $value = (bool) $default;
                }
            } else {
                // Standard integer values. Client value takes precedence.
                $value = $client->getConfig($option);
                if ($value === null) {
                    $value = $default;
                }
            }

            $config[$option] = $value;
        }

        return $config;
    }

    /**
     * Get all explicitly configured client-specific configuration values.
     *
     * Returns an associative array with options which are explicitly configured
     * for the given client. Unconfigured options are not returned.
     *
     * The returned options may not necessarily be effective, because they may
     * be overridden globally or by a group.
     *
     * @return array<string, int|bool|string>
     */
    public function getExplicitConfig(Client $client): array
    {
        $config = [];
        foreach (self::OptionsWithDefaults as $option) {
            $value = $client->getConfig($option);
            if ($value !== null) {
                $config[$option] = $value;
            }
        }
        $scanThisNetwork = $client->getConfig('scanThisNetwork');
        if ($scanThisNetwork !== null) {
            $config['scanThisNetwork'] = $scanThisNetwork;
        }

        return $config;
    }

    /**
     * @template T of Client|Group
     * @param T $object
     * @param (T is Client ? ClientConfigurationParameters : GroupConfigurationParameters) $options
     */
    public function setOptions(Client | Group $object, ConfigurationParameters $options): void
    {
        assert(($object instanceof Client && $options instanceof ClientConfigurationParameters) ||
            ($object instanceof Group && $options instanceof GroupConfigurationParameters));

        foreach ($options as $option => $value) {
            /** @psalm-suppress UnhandledMatchCondition */
            $value = match ($option) {
                'contactInterval',
                'inventoryInterval',
                'packageDeployment',
                'allowScan',
                => $value,
                // If packageDeployment is disabled, $option is irrelevant and will be unset.
                'downloadPeriodDelay',
                'downloadCycleDelay',
                'downloadFragmentDelay',
                'downloadMaxPriority',
                'downloadTimeout',
                => $options->packageDeployment ? $value : null,
                // If allowScan is disabled, $option is irrelevant and will be unset.
                'scanSnmp',
                'scanThisNetwork',
                => $options->allowScan ? $value : null,
            };
            $object->setConfig($option, $value);
        }
    }
}
