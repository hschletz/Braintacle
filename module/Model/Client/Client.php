<?php

/**
 * Client
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

namespace Model\Client;

use Braintacle\Group\Membership;
use Database\Table\AndroidInstallations;
use Database\Table\DuplicateAssetTags;
use Database\Table\DuplicateSerials;
use Database\Table\GroupMemberships;
use Database\Table\PackageHistory;
use Database\Table\WindowsInstallations;
use DateTimeInterface;
use Model\Group\GroupManager;
use Protocol\Message\InventoryRequest;
use ReturnTypeWillChange;

/**
 * Client
 *
 * Additional virtual properties from searched items are provided by filters on
 * an item in the form "Type.Property".
 *
 * @property \Model\Client\AndroidInstallation $android Android installation info, NULL for non-Android systems
 * @property \Model\Client\WindowsInstallation $windows Windows installation info, NULL for non-Windows systems
 * @property \Model\Client\CustomFields $customFields custom fields
 * @property bool $isSerialBlacklisted TRUE if the serial is ignored for detection of duplicates
 * @property bool $isAssetTagBlacklisted TRUE if the asset tag is ignored for detection of duplicates
 * @property \Model\Client\Item\AudioDevice[] $audioDevice audio devices
 * @property \Model\Client\Item\Controller[] $controller controllers
 * @property \Model\Client\Item\Cpu[] $cpu CPUs (newer UNIX clients only)
 * @property \Model\Client\Item\Display[] $display displays
 * @property \Model\Client\Item\DisplayController[] $displayController display controllers
 * @property \Model\Client\Item\ExtensionSlot[] $extensionSlot extension slots
 * @property \Model\Client\Item\Filesystem[] $filesystem filesystems
 * @property \Model\Client\Item\InputDevice[] $inputDevice input devices
 * @property \Model\Client\Item\MemorySlot[] $memorySlot memory slots
 * @property \Model\Client\Item\Modem[] $modem modems
 * @property \Model\Client\Item\MsOfficeProduct[] $msOfficeProduct MS Office products
 * @property \Model\Client\Item\NetworkInterface[] $networkInterface network interfaces
 * @property \Model\Client\Item\Port[] $port ports
 * @property \Model\Client\Item\Printer[] $printer printers
 * @property \Model\Client\Item\RegistryData[] $registryData registry data
 * @property \Model\Client\Item\Sim[] $sim SIM (Android clients only)
 * @property \Model\Client\Item\Software[] $software software
 * @property \Model\Client\Item\StorageDevice[] $storageDevice storage devices
 * @property \Model\Client\Item\VirtualMachine[] $virtualMachine virtual machines
 * @property string $Package.Status package status (supplied by filter)
 * @property Membership $membership group membership type (supplied by filter)
 * @property string $Registry.* registry search result (supplied by filter)
 *
 * @psalm-suppress PossiblyUnusedProperty -- Referenced in template
 */
class Client extends \Model\ClientOrGroup
{
    /**
     * Value denoting automatic group membership, i.e. from a group query
     */
    const MEMBERSHIP_AUTOMATIC = 0;

    /**
     * Value denoting explicit group membership
     */
    const MEMBERSHIP_ALWAYS = 1;

    /**
     * Value denoting that the client is excluded from a group
     */
    const MEMBERSHIP_NEVER = 2;

    /**
     * Value denoting either MEMBERSHIP_ALWAYS or MEMBERSHIP_NEVER - only as argument for getGroupMemberships()
     */
    const MEMBERSHIP_MANUAL = -1;

    /**
     * Value denoting any membership value - only as argument for getGroupMemberships()
     */
    const MEMBERSHIP_ANY = -2;

    /**
     * Primary key
     */
    public int $id;

    /**
     * Client-generated ID (name + timestamp, like 'NAME-2009-04-27-15-52-37')
     */
    public string $idString;

    /**
     * Name
     */
    public string $name;

    /**
     * Type (Desktop, Notebook...) as reported by BIOS
     */
    public ?string $type;

    /**
     * System manufacturer
     */
    public ?string $manufacturer;

    /**
     * Product name
     */
    public ?string $productName;

    /**
     * Serial number
     */
    public ?string $serial;

    /**
     * Asset tag
     */
    public ?string $assetTag;

    /**
     * CPU clock in MHz
     */
    public int $cpuClock;

    /**
     * Total number of CPUs/cores
     */
    public int $cpuCores;

    /**
     * CPU manufacturer and model
     */
    public string $cpuType;

    /**
     * Timestamp of last inventory
     */
    public DateTimeInterface $inventoryDate;

    /**
     * Timestamp of last agent contact (may be newer than $inventoryDate)
     */
    public DateTimeInterface $lastContactDate;

    /**
     * Amount of RAM as reported by OS. May be lower than actual RAM.
     */
    public int $physicalMemory;

    /**
     * Amount of swap space in use
     */
    public int $swapMemory;

    /**
     * BIOS manufacturer
     */
    public ?string $biosManufacturer;

    /**
     * BIOS version
     */
    public ?string $biosVersion;

    /**
     * BIOS date (no unified format, not parseable)
     */
    public ?string $biosDate;

    /**
     * DNS domain name (UNIX clients only)
     */
    public ?string $dnsDomain;

    /**
     * IP Address of DNS server
     */
    public ?string $dnsServer;

    /**
     * Default gateway
     */
    public ?string $defaultGateway;

    /**
     * OS name
     */
    public string $osName;

    /**
     * Internal OS version number
     */
    public ?string $osVersionNumber;

    /**
     * OS version (Service pack, kernel version etc...)
     */
    public ?string $osVersionString;

    /**
     * OS comment
     */
    public ?string $osComment;

    /**
     * User agent identification string
     */
    public string $userAgent;

    /**
     * User logged in at time of inventory
     */
    public ?string $userName;

    /**
     * UUID (typically provided by BIOS)
     */
    public ?string $uuid;

    /**
     * Bitmask of changed inventory sections.
     */
    public int $inventoryDiff;

    /**
     * @deprecated Enumerate all interfaces for a complete list of IP addresses.
     */
    public ?string $ipAddress;

    /**
     * Cache for getGroups() result
     * @var \Model\Group\Group[]
     */
    protected $_groups;

    #[ReturnTypeWillChange]
    public function offsetGet($key)
    {
        $key = ucfirst($key);
        if ($this->offsetExists($key)) {
            $value = parent::offsetGet($key);
        } elseif (strpos($key, 'Registry.') === 0) {
            $value = $this['Registry.Content'];
        } else {
            // Virtual properties from database queries
            switch ($key) {
                case 'Android':
                    $androidInstallations = $this->container->get(AndroidInstallations::class);
                    $select = $androidInstallations->getSql()->select();
                    $select->columns(['javacountry', 'javaname', 'javaclasspath', 'javahome']);
                    $select->where(array('hardware_id' => $this['Id']));
                    $value = $androidInstallations->selectWith($select)->current() ?: null;
                    break;
                case 'Windows':
                    $windowsInstallations = $this->container->get(WindowsInstallations::class);
                    $select = $windowsInstallations->getSql()->select();
                    $select->columns(
                        array(
                            'workgroup',
                            'user_domain',
                            'company',
                            'owner',
                            'product_key',
                            'product_id',
                            'manual_product_key',
                            'cpu_architecture',
                        )
                    );
                    $select->where(array('client_id' => $this['Id']));
                    $value = $windowsInstallations->selectWith($select)->current() ?: null;
                    break;
                case 'CustomFields':
                    $value = $this->container->get(CustomFieldManager::class)->read($this->id);
                    break;
                case 'IsSerialBlacklisted':
                    $duplicateSerials = $this->container->get(DuplicateSerials::class);
                    $value = (bool) $duplicateSerials->select(['serial' => $this->serial])->count();
                    break;
                case 'IsAssetTagBlacklisted':
                    $duplicateAssetTags = $this->container->get(DuplicateAssetTags::class);
                    $value = (bool) $duplicateAssetTags->select(['assettag' => $this->assetTag])->count();
                    break;
                default:
                    $value = $this->getItems($key);
            }
            // Cache result
            $this->offsetSet($key, $value);
        }
        return $value;
    }

    /** {@inheritdoc} */
    public function getExplicitConfig()
    {
        $config = parent::getExplicitConfig();
        $scanThisNetwork = $this->getConfig('scanThisNetwork');
        if ($scanThisNetwork !== null) {
            $config['scanThisNetwork'] = $scanThisNetwork;
        }
        return $config;
    }

    /**
     * Get package IDs from download history
     *
     * @return array Package IDs (creation timestamps)
     */
    public function getDownloadedPackageIds()
    {
        $packageHistory = $this->container->get(PackageHistory::class);
        $select = $packageHistory->getSql()->select();
        $select->columns(array('pkg_id'))
            ->where(array('hardware_id' => $this['Id']))
            ->order('pkg_id');
        return array_column($packageHistory->selectWith($select)->toArray(), 'pkg_id');
    }

    /**
     * Get all items of given type
     *
     * @param string $type Item type
     * @param string $order Property to sort by. Default: item-specific
     * @param string $direction Sorting direction (asc|desc)
     * @param array $filters Extra filters for ItemManager::getItems()
     * @return \Laminas\Db\ResultSet\AbstractResultSet
     */
    public function getItems($type, $order = null, $direction = null, $filters = array())
    {
        $filters['Client'] = $this['Id'];
        return $this->container->get(ItemManager::class)->getItems(
            $type,
            $filters,
            $order,
            $direction
        );
    }

    /**
     * Set group memberships
     *
     * Groups which are not present in $newGroups remain unchanged. The keys can
     * be either the integer ID or the name of the group.
     *
     * @param array $newMemberships New group memberships (integer|string => Type). Unknown groups are ignored.
     */
    public function setGroupMemberships($newMemberships)
    {
        $groupMemberships = $this->container->get(GroupMemberships::class);

        // Build lookup tables
        $groupsById = array();
        $groupsByName = array();
        foreach ($this->container->get(GroupManager::class)->getGroups() as $group) {
            $groupsById[$group['Id']] = $group;
            $groupsByName[$group['Name']] = $group;
        }
        $oldMemberships = $this->getGroupMemberships(self::MEMBERSHIP_ANY);

        $resetCache = false;
        foreach ($newMemberships as $groupKey => $newMembership) {
            if (is_int($groupKey)) {
                $group = @$groupsById[$groupKey];
            } else {
                $group = @$groupsByName[$groupKey];
            }
            if (!$group) {
                continue; // Ignore unknown groups
            }

            $groupId = $group['Id'];
            if (isset($oldMemberships[$groupId])) {
                $oldMembership = $oldMemberships[$groupId];
            } else {
                $oldMembership = null;
            }
            switch ($newMembership) {
                case self::MEMBERSHIP_AUTOMATIC:
                    if (
                        $oldMembership === self::MEMBERSHIP_ALWAYS or
                        $oldMembership === self::MEMBERSHIP_NEVER
                    ) {
                        // Delete manual membership and update group cache
                        // because the client may be a candidate for automatic
                        // membership.
                        $groupMemberships->delete(
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                            )
                        );
                        $group->update(true);
                        $resetCache = true;
                    }
                    break;
                case self::MEMBERSHIP_ALWAYS:
                case self::MEMBERSHIP_NEVER:
                    if ($oldMembership === null) {
                        $groupMemberships->insert(
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                                'static' => $newMembership,
                            )
                        );
                        $resetCache = true;
                    } elseif ($oldMembership !== $newMembership) {
                        $groupMemberships->update(
                            array(
                                'static' => $newMembership
                            ),
                            array(
                                'hardware_id' => $this['Id'],
                                'group_id' => $groupId,
                            )
                        );
                        $resetCache = true;
                    }
                    break;
                default:
                    throw new \InvalidArgumentException('Invalid membership type: ' . $newMembership);
            }
        }

        if ($resetCache) {
            $this->_groups = null;
        }
    }

    /**
     * Retrieve group membership information
     *
     * @param integer $membershipType Membership type (one of the MEMBERSHIP_* constants)
     * @return array Group ID => membership type
     * @throws \InvalidArgumentException if $membershipType is invalid
     */
    public function getGroupMemberships($membershipType)
    {
        $groupMemberships = $this->container->get(GroupMemberships::class);
        $select = $groupMemberships->getSql()->select();
        $select->columns(array('group_id', 'static'));

        switch ($membershipType) {
            case self::MEMBERSHIP_ANY:
                break;
            case self::MEMBERSHIP_MANUAL:
                $select->where(
                    new \Laminas\Db\Sql\Predicate\Operator('static', '!=', self::MEMBERSHIP_AUTOMATIC)
                );
                break;
            case self::MEMBERSHIP_AUTOMATIC:
            case self::MEMBERSHIP_ALWAYS:
            case self::MEMBERSHIP_NEVER:
                $select->where(array('static' => $membershipType));
                break;
            default:
                throw new \InvalidArgumentException("Bad value for membership: $membershipType");
        }
        $select->where(array('hardware_id' => $this['Id']));

        $this->container->get(GroupManager::class)->updateCache();

        $result = array();
        foreach ($groupMemberships->selectWith($select) as $row) {
            $result[(int) $row['group_id']] = (int) $row['static'];
        }
        return $result;
    }

    /**
     * Get groups of which this client is a member
     *
     * Result gets cached.
     *
     * @return \Model\Group\Group[]
     */
    public function getGroups()
    {
        if ($this->_groups === null) {
            $this->_groups = iterator_to_array(
                $this->container->get(GroupManager::class)->getGroups('Member', $this->id)
            );
        }
        return $this->_groups;
    }
    /**
     * Set values for custom fields
     *
     * @param array $values Field name => Value
     */
    public function setCustomFields($values)
    {
        $this->container->get(CustomFieldManager::class)->write($this->id, $values);
    }

    /**
     * Export to DOM document.
     */
    public function toDomDocument(): InventoryRequest
    {
        $document = clone $this->container->get(InventoryRequest::class);
        $document->formatOutput = true;
        $document->loadClient($this);
        return $document;
    }
}
