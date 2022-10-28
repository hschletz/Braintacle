<?php

/**
 * CONTENT element of an InventoryRequest document
 *
 * Copyright (C) 2011-2022 Holger Schletz <holger.schletz@web.de>
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

namespace Protocol\Message\InventoryRequest;

use Laminas\Hydrator\HydratorInterface;
use Model\Client\Client;
use Model\Client\ItemManager;
use PhpBench\Dom\Element;
use Protocol\Hydrator;
use Psr\Container\ContainerInterface;

/**
 * CONTENT element of an InventoryRequest document
 */
class Content extends Element
{
    /**
     * Name of 'HARDWARE' section
     */
    const SYSTEM_SECTION_HARDWARE = 'HARDWARE';

    /**
     * Name of 'BIOS' section
     */
    const SYSTEM_SECTION_BIOS = 'BIOS';

    /**
     * Map of item types to section names
     * @var array
     */
    protected static $itemSections = [
        'controller' => 'CONTROLLERS',
        'cpu' => 'CPUS',
        'filesystem' => 'DRIVES',
        'inputdevice' => 'INPUTS',
        'memoryslot' => 'MEMORIES',
        'modem' => 'MODEMS',
        'display' => 'MONITORS',
        'networkinterface' => 'NETWORKS',
        'msofficeproduct' => 'OFFICEPACK',
        'port' => 'PORTS',
        'printer' => 'PRINTERS',
        'registrydata' => 'REGISTRY',
        'sim' => 'SIM',
        'extensionslot' => 'SLOTS',
        'software' => 'SOFTWARES',
        'audiodevice' => 'SOUNDS',
        'storagedevice' => 'STORAGES',
        'displaycontroller' => 'VIDEOS',
        'virtualmachine' => 'VIRTUALMACHINES',
    ];

    /**
     * Source client.
     * @var Client
     */
    protected $client;

    /**
     * Service locator.
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct('CONTENT');
        $this->container = $container;
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Append all sections.
     */
    public function appendSections(): void
    {
        $this->appendSystemSection(self::SYSTEM_SECTION_HARDWARE);
        $this->appendSystemSection(self::SYSTEM_SECTION_BIOS);
        $this->appendOsSpecificSection();
        $this->appendAccountinfoSection();
        $this->appendDownloadSection();
        $this->appendAllItemSections();
    }

    /**
     * Append "HARDWARE" or "BIOS" section.
     *
     * @param string $section One of the SYSTEM_SECTION_* constants
     */
    public function appendSystemSection(string $section): void
    {
        switch ($section) {
            case self::SYSTEM_SECTION_HARDWARE:
                $hydrator = $this->container->get(Hydrator\ClientsHardware::class);
                break;
            case self::SYSTEM_SECTION_BIOS:
                $hydrator = $this->container->get(Hydrator\ClientsBios::class);
                break;
            default:
                throw new \InvalidArgumentException('Invalid section name: ' . $section);
        }

        $data = $hydrator->extract($this->client);
        ksort($data);
        $this->appendSection($section, $data);
    }

    /**
     * Append OS-Specific section.
     */
    public function appendOsSpecificSection(): void
    {
        $android = $this->client['Android'];
        if ($android) {
            $data = $this->container->get('Protocol\Hydrator\AndroidInstallations')->extract($android);
            $this->appendSection('JAVAINFOS', $data);
        }
    }

    /**
     * Append ACCOUNTINFO section.
     */
    public function appendAccountinfoSection(): void
    {
        foreach ($this->client['CustomFields'] as $property => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d');
            }
            if ((string) $value != '') {
                $this->appendSection(
                    'ACCOUNTINFO',
                    [
                        'KEYNAME' => $property,
                        'KEYVALUE' => $value,
                    ]
                );
            }
        }
    }

    /**
     * Append DOWNLOAD section.
     */
    public function appendDownloadSection(): void
    {
        $packages = $this->client->getDownloadedPackageIds();
        if ($packages) {
            // DOWNLOAD section has 1 HISTORY element with 1 PACKAGE element per package.
            $download = $this->appendElement('DOWNLOAD');
            $history = $download->appendElement('HISTORY');
            foreach ($packages as $id) {
                $package = $history->appendElement('PACKAGE');
                $package->setAttribute('ID', $id);
            }
        }
    }

    /**
     * Append sections for all item types.
     */
    public function appendAllItemSections(): void
    {
        foreach (static::$itemSections as $type => $section) {
            $this->appendItemSections($type, $section);
        }
    }

    /**
     * Append sections for given item type.
     */
    public function appendItemSections(string $itemType, string $section): void
    {
        $items = $this->client->getItems($itemType, 'id', 'asc');
        $table = $this->container->get(ItemManager::class)->getTableName($itemType);
        /** @var HydratorInterface */
        $hydrator = $this->container->get("Protocol\\Hydrator\\$table");
        /** @var object */
        foreach ($items as $item) {
            $this->appendSection($section, $hydrator->extract($item));
        }
    }

    /**
     * Append section.
     */
    public function appendSection(string $name, array $content): void
    {
        $element = $this->appendElement($name);
        foreach ($content as $key => $value) {
            if ((string) $value != '') {
                $element->appendTextNode($key, $value);
            }
        }
    }
}
