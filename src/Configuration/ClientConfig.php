<?php

namespace Braintacle\Configuration;

use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Model\Client\Client;
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
     * Get default values for client/group options.
     *
     * These defaults are effective if an option is not explicitly configured
     * for a client/group.
     */
    public function getDefaults(Client | Group $object): array
    {
        foreach (self::OptionsWithDefaults as $option) {
            $default = $object->getDefaultConfig($option);
            if (in_array($option, self::BooleanOptions)) {
                $default = (bool) $default;
            }
            $defaults[$option] = $default;
        }

        return $defaults;
    }

    /**
     * Get effective values for client options.
     *
     * These vaules are effective for the given client - either the explicitly
     * configured value or defaults from groups or global configuration,
     * whatever takes precedence.
     */
    public function getEffectiveConfig(Client $client): array
    {
        foreach (self::OptionsWithDefaults as $option) {
            $value = $client->getEffectiveConfig($option);
            if (in_array($option, self::BooleanOptions)) {
                $value = (bool) $value;
            }
            $config[$option] = $value;
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
