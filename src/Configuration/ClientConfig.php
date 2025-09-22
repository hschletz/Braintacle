<?php

namespace Braintacle\Configuration;

use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Database\Table;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Doctrine\DBAL\Connection;
use Model\Client\Client;
use Model\ClientOrGroup;
use Model\Config;
use Model\Group\Group;
use Throwable;

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

    public function __construct(private Config $config, private Connection $connection) {}

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
            $value = $this->getOption($object, $option);
            if (in_array($option, self::BooleanOptions)) {
                // These options can only be disabled ($value is 0) or
                // unconfigured ($value is NULL), in which case the default is
                // effective. Map condition to a boolean value.
                /** @psalm-suppress UnhandledMatchCondition */
                $value = match ($value) {
                    null => true,
                    false => false,
                };
            }
            $options[$option] = $value;
        }

        if ($object instanceof Client) {
            $options['scanThisNetwork'] = $this->getOption($object, 'scanThisNetwork');
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
                $groupValue = $this->getOption($group, $option);
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
                    $value = $this->getOption($client, 'inventoryInterval');
                    foreach ($client->getGroups() as $group) {
                        $groupValue = $this->getOption($group, 'inventoryInterval');
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
                if ($default && $this->getOption($client, $option) === 0) {
                    $value = false;
                } else {
                    $value = (bool) $default;
                }
            } else {
                // Standard integer values. Client value takes precedence.
                $value = $this->getOption($client, $option);
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
            $value = $this->getOption($client, $option);
            if ($value !== null) {
                $config[$option] = $value;
            }
        }
        $scanThisNetwork = $this->getOption($client, 'scanThisNetwork');
        if ($scanThisNetwork !== null) {
            $config['scanThisNetwork'] = $scanThisNetwork;
        }

        return $config;
    }

    /**
     * Get configuration value.
     *
     * Returns configuration values stored for the given client/group. If no
     * explicit configuration is stored, NULL is returned. A returned setting is
     * not necessarily in effect - it may be overridden somewhere else.
     *
     * Any valid global option name can be passed for $option, though most
     * options are not object-specific and would always yield NULL. In addition
     * to the global options, the following options are available:
     *
     * - **allowScan:** If FALSE, prevents client or group members from scanning
     *   networks.
     *
     * - **scanThisNetwork:** Causes a client to always scan networks with the
     *   given address (not taking a network mask into account), overriding the
     *   server's automatic choice.
     *
     * packageDeployment, allowScan and scanSnmp are never evaluated if disabled
     * globally or by groups of which a client is a member. For this reason,
     * these options can only be FALSE (explicitly disabled if enabled on a
     * higher level) or NULL (inherit behavior).
     */
    public function getOption(Client | Group $object, string $option): int | string | bool | null
    {
        $name = match ($option) {
            'packageDeployment' => 'DOWNLOAD_SWITCH',
            'scanSnmp' => 'SNMP_SWITCH',
            'allowScan', 'scanThisNetwork' => 'IPDISCOVER',
            default => $name = $this->config->getDbIdentifier($option),
        };
        $ivalue = match ($option) {
            'allowScan' => ClientOrGroup::SCAN_DISABLED,
            'scanThisNetwork' => ClientOrGroup::SCAN_EXPLICIT,
            default => null,
        };
        $column = ($option == 'scanThisNetwork') ? 'tvalue' : 'ivalue';

        $select = $this->connection
            ->createQueryBuilder()
            ->select($column)
            ->from(Table::ClientConfig)
            ->where('hardware_id = :id', 'name = :name')
            ->setParameter('id', $object->id)
            ->setParameter('name', $name);
        if (isset($ivalue)) {
            $select->andWhere('ivalue = :ivalue')->setParameter('ivalue', $ivalue);
        }

        $value = $select->fetchOne();
        if ($value === false) { // no row found
            $value = null;
        } elseif (in_array($option, self::BooleanOptions)) {
            $value = $value ? null : false;
        } elseif ($column == 'ivalue') {
            $value = (int) $value;
        }

        return $value;
    }

    public function setOption(Client | Group $object, string $option, int | bool | string | null $value): void
    {
        // Determine 'name' column in the ClientConfig table
        if ($option == 'allowScan' || $option == 'scanThisNetwork') {
            $name = 'IPDISCOVER';
        } else {
            $name = $this->config->getDbIdentifier($option);
            if ($option == 'packageDeployment' || $option == 'scanSnmp') {
                $name .= '_SWITCH';
            }
        }

        // Set affected columns
        if ($option == 'scanThisNetwork') {
            assert($value === null || is_string($value));
            $columns = [
                'ivalue' => Client::SCAN_EXPLICIT,
                'tvalue' => $value,
            ];
        } else {
            if ($value !== null && in_array($option, self::BooleanOptions)) {
                assert(is_bool($value));
                // These options are only evaluated if their default setting is
                // enabled, i.e. they only have an effect if they get disabled.
                // To keep things clearer in the database, the option is unset
                // if enabled, with the same effect (i.e. none).
                $value = $value ? null : 0;
            } else {
                assert($value === null || is_int($value));
            }
            $columns = ['ivalue' => $value];
        }

        // Filter for delete()/update()
        $condition = [
            'hardware_id' => $object->id,
            'name' => $name,
        ];

        $this->connection->beginTransaction();
        try {
            if ($value === null) {
                // Unset option. For scan options, also check ivalue to prevent
                // accidental deletion of unrelated setting.
                if ($option == 'allowScan') {
                    $condition['ivalue'] = Client::SCAN_DISABLED;
                } elseif ($option == 'scanThisNetwork') {
                    $condition['ivalue'] = Client::SCAN_EXPLICIT;
                }
                $this->connection->delete(Table::ClientConfig, $condition);
            } else {
                $oldValue = $this->getOption($object, $option);
                if ($oldValue === null) {
                    // Not set yet, insert new record
                    if ($name == 'IPDISCOVER' or $name == 'DOWNLOAD_SWITCH' or $name == 'SNMP_SWITCH') {
                        // There may already be a record with a different
                        // ivalue. For IPDISCOVER, this can happen because
                        // different $option values map to it. For *_SWITCH,
                        // this can happen if the database value is 1 (which is
                        // only possible if the record was not written by
                        // Braintacle), which getConfig() reports as NULL. Since
                        // there may only be 1 record per hardware_id/name, the
                        // old record must be deleted first.
                        $this->connection->delete(Table::ClientConfig, $condition);
                    }
                    $columns['hardware_id'] = $object->id;
                    $columns['name'] = $name;
                    $this->connection->insert(Table::ClientConfig, $columns);
                } elseif ($oldValue != $value) {
                    // Already set to a different value, update record
                    $this->connection->update(Table::ClientConfig, $columns, $condition);
                }
            }
            $this->connection->commit();
        } catch (Throwable $throwable) {
            $this->connection->rollBack();
            throw $throwable;
        }
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
            $this->setOption($object, $option, $value);
        }
    }
}
