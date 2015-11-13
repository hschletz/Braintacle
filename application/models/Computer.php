<?php
/**
 * Class representing a computer
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
 *
 * @package Models
 */
/**
 * A single computer which is inventoried by OCS agent
 *
 * Properties:
 *
 * - <b>Id:</b> primary key
 * - <b>ClientId:</b> Client-generated ID (name + timestamp, like 'COMPUTERNAME-2009-04-27-15-52-37')
 * - <b>Name:</b> computer name
 * - <b>Type:</b> computer type (Desktop, Notebook...) as reported by BIOS
 * - <b>Manufacturer:</b> system manufacturer
 * - <b>Model:</b> system model
 * - <b>Serial:</b> serial number
 * - <b>AssetTag:</b> asset tag
 * - <b>CpuClock:</b> CPU clock in MHz
 * - <b>CpuCores:</b> total number of CPUs/cores
 * - <b>CpuType:</b> CPU manufacturer and model
 * - <b>InventoryDate:</b> timestamp of last inventory
 * - <b>LastContactDate:</b> timestamp of last agent contact (may be newer than InventoryDate)
 * - <b>PhysicalMemory:</b> Amount of RAM as reported by OS. May be lower than actual RAM.
 * - <b>SwapMemory:</b> Amount of swap space in use
 * - <b>BiosManufacturer:</b> BIOS manufacturer
 * - <b>BiosVersion:</b> BIOS version
 * - <b>BiosDate:</b> BIOS date
 * - <b>IpAddress:</b> IP Adress
 * - <b>DnsDomain:</b> DNS domain name (UNIX clients only)
 * - <b>DnsServer:</b> IP Address of DNS server
 * - <b>DefaultGateway:</b> default gateway
 * - <b>OcsAgent:</b> name and version of OCS agent
 * - <b>OsName:</b> OS name (may be processed by getProperty())
 * - <b>OsVersionNumber:</b> internal OS version number
 * - <b>OsVersionString:</b> OS version (Service pack, kernel version etc...)
 * - <b>OsComment:</b> comment
 * - <b>UserName:</b> User logged in at time of inventory
 * - <b>Uuid</b> UUID, typically found in virtual machines
 * - <b>Windows:</b> \Model\Client\WindowsInstallation object, NULL for non-Windows systems
 * - <b>CustomFields:</b> \Model\Client\CustomFields object
 * - <b>IsSerialBlacklisted:</b> TRUE if the serial number is blacklisted, i.e. ignored for detection of duplicates.
 * - <b>IsAssetTagBlacklisted:</b> TRUE if the asset tag is blacklisted, i.e. ignored for detection of duplicates.
 * - <b>AudioDevice, Controller, Display, DisplayController, ExtensionSlot,
 *   InputDevice, Port, MemorySlot, Modem, MsOfficeProduct, NetworkInterface,
 *   Printer, RegistryData, Software, StorageDevice, VirtualMachine, Filesystem:</b>
 *   A list of all items of the given type. Equivalent of calling getItems()
 *   without extra arguments.
 *
 * Properties containing a '.' character refer to child objects. These properties are:
 *
 * - <b>Package.Status</b> Deployment status (raw value from download_enable.tvalue)
 *
 *
 * Additionally, properties of child objects from a joined query are accessible
 * too. To make this work, an alias for the column has to be specified in the
 * form 'model_property'. Example:
 * <code>SELECT hardware.name, storages.disksize AS storagedevice_size...</code>
 * This would make the property 'StorageDevice.Size' available to this class.
 * Note that only properties defined by the model class will work.
 * The model prefix ensures that ambiguous properties/columns will not clash.
 *
 * If the 'MemberOf' filter is applied, the <b>Membership</b> property is
 * available which contains one of the {@link Model_GroupMembership} constants.
 *
 * Windows-specific information is available through the public 'windows' member
 * and through the 'Windows.*' property.
 *
 * The properties "Registry.*" refer to the combined value and data of a defined
 * registry value with the given name.
 *
 * @package Models
 */
abstract class Model_Computer extends \Model_Abstract
{
    /**
     * Set values for the user defined fields for this computer.
     * @param array $values Associative array with field names as keys.
     */
    public function setUserDefinedInfo($values)
    {
        \Library\Application::getService('Model\Client\CustomFieldManager')->write($this['Id'], $values);
    }

    /**
     * Export computer as DOMDocument
     * @return \Protocol\Message\InventoryRequest
     */
    public function toDomDocument()
    {
        $document = new \Protocol\Message\InventoryRequest;
        $document->loadClient($this, \Library\Application::getService('ServiceManager'));
        return $document;
    }
}
