<?php
/**
 * Client
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

namespace Model\Client;

/**
 * Client
 *
 * @property integer $Id primary key
 * @property string $ClientId client-generated ID (name + timestamp, like 'COMPUTERNAME-2009-04-27-15-52-37')
 * @property string $Name computer name
 * @property string $Type computer type (Desktop, Notebook...) as reported by BIOS
 * @property string $Manufacturer system manufacturer
 * @property string $Model system model
 * @property string $Serial serial number
 * @property string $AssetTag asset tag
 * @property integer $CpuClock CPU clock in MHz
 * @property integer $CpuCores total number of CPUs/cores
 * @property string $CpuType CPU manufacturer and model
 * @property \DateTime $InventoryDate timestamp of last inventory
 * @property \DateTime $LastContactDate timestamp of last agent contact (may be newer than InventoryDate)
 * @property integer $PhysicalMemory Amount of RAM as reported by OS. May be lower than actual RAM.
 * @property integer $SwapMemory Amount of swap space in use
 * @property string $BiosManufacturer BIOS manufacturer
 * @property string $BiosVersion BIOS version
 * @property string $BiosDate BIOS date (no unified format, not parseable)
 * @property string $DnsServer IP Address of DNS server
 * @property string $DefaultGateway default gateway
 * @property string $OcsAgent user agent identification string
 * @property string $OsName OS name
 * @property string $OsVersionNumber internal OS version number
 * @property string $OsVersionString OS version (Service pack, kernel version etc...)
 * @property string $OsComment OS comment
 * @property string $UserName user logged in at time of inventory
 * @property string $Uuid UUID (typically provided by BIOS)
 * @property \Model\Client\WindowsInstallation $Windows Windows installation info, NULL for non-Windows systems
 * @property \Model\Client\CustomFields $CustomFields custom fields
 * @property bool $IsSerialBlacklisted TRUE if the serial is ignored for detection of duplicates
 * @property bool $IsAssetTagBlacklisted TRUE if the asset tag is ignored for detection of duplicates
 * @property \Model\Client\Item\AudioDevice[] $AudioDevice audio devices
 * @property \Model\Client\Item\Controller[] $Controller controllers
 * @property \Model\Client\Item\Display[] $Display displays
 * @property \Model\Client\Item\DisplayController[] $DisplayController display controllers
 * @property \Model\Client\Item\ExtensionSlot[] $ExtensionSlot extension slots
 * @property \Model\Client\Item\Filesystem[] $Filesystem filesystems
 * @property \Model\Client\Item\InputDevice[] $InputDevice input devices
 * @property \Model\Client\Item\MemorySlot[] $MemorySlot memory slots
 * @property \Model\Client\Item\Modem[] $Modem modems
 * @property \Model\Client\Item\MsOfficeProduct[] $MsOfficeProduct MS Office products
 * @property \Model\Client\Item\NetworkInterface[] $NetworkInterface network interfaces
 * @property \Model\Client\Item\Port[] $Port ports
 * @property \Model\Client\Item\Printer[] $Printer printers
 * @property \Model\Client\Item\RegistryData[] $RegistryData registry data
 * @property \Model\Client\Item\Software[] $Software software
 * @property \Model\Client\Item\StorageDevice[] $StorageDevice storage devices
 * @property \Model\Client\Item\VirtualMachine[] $VirtualMachine virtual machines
 * @property string $Package.Status package status (supplied by filter)
 * @property integer $Membership group membership type (supplied by filter)
 * @property string $Registry.* registry search result (supplied by filter)
 *
 * Additional virtual properties from searched items are provided by filters on
 * an item in the form "Type.Property".
 */
class Client extends \Model_Computer
{
    /** {@inheritdoc} */
    public function offsetGet($index)
    {
        if ($this->offsetExists($index)) {
            $value = parent::offsetGet($index);
        } elseif (strpos($index, 'Registry.') === 0) {
            $value = $this['Registry.Content'];
        } else {
            // Virtual properties from database queries
            switch ($index) {
                case 'Windows':
                    $windowsInstallations = $this->serviceLocator->get('Database\Table\WindowsInstallations');
                    $select = $windowsInstallations->getSql()->select();
                    $select->columns(
                        array(
                            'workgroup',
                            'user_domain',
                            'company',
                            'owner',
                            'product_key',
                            'product_id',
                            'manual_product_key'
                        )
                    );
                    $select->where(array('client_id' => $this['Id']));
                    $value = $windowsInstallations->selectWith($select)->current() ?: null;
                    break;
                case 'CustomFields':
                    $value = $this->serviceLocator->get('Model\Client\CustomFieldManager')->read($this['Id']);
                    break;
                case 'IsSerialBlacklisted':
                    $duplicateSerials = $this->serviceLocator->get('Database\Table\DuplicateSerials');
                    $value = (bool) $duplicateSerials->select(array('serial' => $this['Serial']))->count();
                    break;
                case 'IsAssetTagBlacklisted':
                    $duplicateAssetTags = $this->serviceLocator->get('Database\Table\DuplicateAssetTags');
                    $value = (bool) $duplicateAssetTags->select(array('assettag' => $this['AssetTag']))->count();
                    break;
                default:
                    $value = $this->getItems($index);
            }
            // Cache result
            $this->offsetSet($index, $value);
        }
        return $value;
    }
}
