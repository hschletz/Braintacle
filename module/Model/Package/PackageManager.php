<?php

/**
 * Package manager
 *
 * Copyright (C) 2011-2026 Holger Schletz <holger.schletz@web.de>
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

namespace Model\Package;

use Database\Table\ClientConfig;
use Database\Table\GroupInfo;
use Database\Table\Packages;
use Laminas\Db\Sql\Predicate;
use Model\Package\Storage\StorageInterface;
use Psr\Container\ContainerInterface;

/**
 * Package manager
 */
class PackageManager
{
    public function __construct(private ContainerInterface $container) {}

    /**
     * Check for existing package
     *
     * @param string $name Package name
     * @return bool
     */
    public function packageExists($name)
    {
        $packages = $this->container->get(Packages::class);
        $sql = $packages->getSql()->select()->columns(array('name'))->where(array('name' => $name));
        return (bool) $packages->selectWith($sql)->count();
    }

    /**
     * Retrieve existing package
     *
     * @param string $name Package name
     * @return \Model\Package\Package Package object containing all data except content and deployment statistics
     * @throws RuntimeException if no package with given name exists or an error occurs
     */
    public function getPackage($name)
    {
        $packages = $this->container->get(Packages::class);
        $storage = $this->container->get(StorageInterface::class);

        $select = $packages->getSql()->select();
        $select->columns(array('fileid', 'name', 'priority', 'fragments', 'size', 'osname', 'comment'))
            ->where(array('name' => $name));

        try {
            $packages = $packages->selectWith($select);
            if (!$packages->count()) {
                throw new \RuntimeException("There is no package with name '$name'");
            }

            $package = $packages->current();
            $package->exchangeArray($storage->readMetadata($package['Id']) + $package->getArrayCopy());
            return $package;
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Return all packages including deployment statistics
     *
     * @param string $order Property to sort by
     * @param string $direction One of [asc|desc]
     * @return \Laminas\Db\ResultSet\AbstractResultSet Result set producing \Model\Package\Package
     */
    public function getPackages($order = null, $direction = 'asc')
    {
        $clientConfig = $this->container->get(ClientConfig::class);
        $groupInfo = $this->container->get(GroupInfo::class);
        $packages = $this->container->get(Packages::class);

        // Subquery prototype for deployment statistics
        $subquery = $clientConfig->getSql()->select();
        $subquery->columns(array(new Predicate\Literal('COUNT(hardware_id)')))
            ->where(
                array('name' => 'DOWNLOAD', 'ivalue' => new \Laminas\Db\Sql\Literal('fileid'))
            );

        $groups = $groupInfo->getSql()->select()->columns(array('hardware_id'));
        $pending = clone $subquery;
        $pending->where(new Predicate\IsNull('tvalue'))
            ->where(new Predicate\NotIn('hardware_id', $groups));

        $running = clone $subquery;
        $running->where(array('tvalue' => \Model\Package\Assignment::RUNNING));

        $success = clone $subquery;
        $success->where(array('tvalue' => \Model\Package\Assignment::SUCCESS));

        $error = clone $subquery;
        $error->where(new Predicate\Like('tvalue', \Model\Package\Assignment::ERROR_PREFIX . '%'));

        $select = $packages->getSql()->select();
        $select->columns(
            array(
                'fileid',
                'name',
                'priority',
                'fragments',
                'size',
                'osname',
                'comment',
                'num_pending' => new Predicate\Expression('?', array($pending)),
                'num_running' => new Predicate\Expression('?', array($running)),
                'num_success' => new Predicate\Expression('?', array($success)),
                'num_error' => new Predicate\Expression('?', array($error)),
            )
        );

        if ($order) {
            if ($order == 'Timestamp') {
                $order = 'fileid';
            } else {
                $order = $packages->getHydrator()->extractName($order);
            }
            $select->order(array($order => $direction));
        }

        return $packages->selectWith($select);
    }

    /**
     * Get all package names
     *
     * @return string[]
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getAllNames()
    {
        return $this->container->get(Packages::class)->fetchCol('name');
    }

    /**
     * Build a package
     *
     * @param array $data Package data
     * @param bool $deleteSource Delete source file as soon as possible
     * @throws RuntimeException if a package with the requested name already exists or an error occurs
     */
    public function buildPackage(array $data, bool $deleteSource): void
    {
        $this->container->get(PackageBuilder::class)->buildPackage($data, $deleteSource);
    }

    /**
     * Delete a package
     *
     * @param string $name Package name
     * @throws RuntimeException if an error occurs
     */
    public function deletePackage($name)
    {
        $packages = $this->container->get(Packages::class);
        $clientConfig = $this->container->get(ClientConfig::class);
        $storage = $this->container->get(StorageInterface::class);
        try {
            $select = $packages->getSql()->select()->columns(array('fileid'))->where(array('name' => $name));
            $package = $packages->selectWith($select)->current();
            if (!$package) {
                throw new \RuntimeException("Package '$name' does not exist");
            }
            $id = $package['Id'];
            $clientConfig->delete(
                array(
                    'ivalue' => $id,
                    "name != 'DOWNLOAD_SWITCH'",
                    "name LIKE 'DOWNLOAD%'",
                )
            );
            $packages->delete(array('fileid' => $id));
            $storage->cleanup($id);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
