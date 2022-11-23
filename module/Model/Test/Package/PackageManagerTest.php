<?php

/**
 * Tests for Model\Package\PackageManager
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

namespace Model\Test\Package;

use Database\Table\ClientConfig;
use Database\Table\GroupInfo;
use Database\Table\Packages;
use DateTime;
use Laminas\ServiceManager\ServiceManager;
use Model\Package\Package;
use Model\Package\PackageBuilder;
use Model\Package\PackageManager;
use Model\Package\Storage\Direct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests for Model\Package\PackageManager
 */
class PackageManagerTest extends \Model\Test\AbstractTest
{
    /** {@inheritdoc} */
    protected static $_tables = array('Config', 'Packages', 'PackageDownloadInfo', 'ClientConfig', 'GroupInfo');

    public function testPackageExists()
    {
        $this->assertTrue($this->getModel()->packageExists('package1'));
        $this->assertFalse($this->getModel()->packageExists('new_package'));
    }

    public function testGetPackage()
    {
        $packageData = array(
            'Id' => '1415958320',
            'Name' => 'package2',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'linux',
            'Comment' => 'Existing package 2',
        );
        $metadata = array(
            'DeployAction' => 'DeployAction',
            'ActionParam' => 'ActionParam',
            'Warn' => 'Warn',
            'WarnMessage' => 'WarnMessage',
            'WarnCountdown' => 'WarnCountdown',
            'WarnAllowAbort' => 'WarnAllowAbort',
            'WarnAllowDelay' => 'WarnAllowDelay',
            'PostInstMessage' => 'PostInstMessage',
        );
        $storage = $this->createMock('Model\Package\Storage\Direct');
        $storage->expects($this->once())->method('readMetadata')->with('1415958320')->willReturn($metadata);

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            [Direct::class, $storage],
            [Packages::class, static::$serviceManager->get(Packages::class)],
        ]);

        $model = new PackageManager($serviceManager);

        $package = $model->getPackage('package2');
        $this->assertInstanceOf('Model\Package\Package', $package);
        $this->assertEquals(
            $packageData + $metadata + array('Timestamp' => new \DateTime('@1415958320')),
            $package->getArrayCopy()
        );
    }

    public function testGetPackageInvalidName()
    {
        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage("There is no package with name 'invalid'");
        $model = $this->getModel();
        $model->getPackage('invalid');
    }

    public function testGetPackageError()
    {
        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage('metadata error');
        $storage = $this->createMock('Model\Package\Storage\Direct');
        $storage->method('readMetadata')->will($this->throwException(new \RuntimeException('metadata error')));

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            [Direct::class, $storage],
            [Packages::class, static::$serviceManager->get(Packages::class)],
        ]);

        $model = new PackageManager($serviceManager);

        $model->getPackage('package1');
    }

    public function getPackagesProvider()
    {
        $package1 =  array (
            'Timestamp' => new \DateTime('@1415958319'),
            'Name' => 'package1',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'windows',
            'Comment' => 'Existing package 1',
            'Id' => '1415958319',
            'NumPending' => '1',
            'NumRunning' => '1',
            'NumSuccess' => '1',
            'NumError' => '1',
        );
        $package2 =  array (
            'Timestamp' => new \DateTime('@1415958320'),
            'Name' => 'package2',
            'Priority' => '5',
            'NumFragments' => '42',
            'Size' => '12345678',
            'Platform' => 'linux',
            'Comment' => 'Existing package 2',
            'Id' => '1415958320',
            'NumPending' => '1',
            'NumRunning' => '0',
            'NumSuccess' => '0',
            'NumError' => '0',
        );
        return array(
            array(null, null, $package1, $package2),
            array('Timestamp', 'asc', $package1, $package2),
            array('Timestamp', 'desc', $package2, $package1),
            array('Name', 'asc', $package1, $package2),
            array('Name', 'desc', $package2, $package1),
            array('NumSuccess', 'asc', $package2, $package1),
            array('NumSuccess', 'desc', $package1, $package2),
        );
    }

    /**
     * Test getPackages()
     *
     * @param string $order tested sort order
     * @param string $direction tested sort direction
     * @param array $package1 first package in the expected result
     * @param array $package2 second package in the expected result
     * @dataProvider getPackagesProvider
     */
    public function testGetPackages($order, $direction, $package1, $package2)
    {
        $model = $this->getModel();
        $packages = iterator_to_array($model->getPackages($order, $direction));
        $this->assertContainsOnlyInstancesOf('Model\Package\Package', $packages);
        $this->assertEquals($package1, $packages[0]->getArrayCopy());
        $this->assertEquals($package2, $packages[1]->getArrayCopy());
    }

    public function testGetAllNames()
    {
        $model = $this->getModel();
        $this->assertEquals(array('package1', 'package2'), $model->getAllNames());
    }

    public function testGetAllNamesEmpty()
    {
        $model = $this->getModel();
        static::$serviceManager->get('Database\Table\Packages')->delete(true);
        $this->assertEquals(array(), $model->getAllNames());
    }

    public function testBuildPackage()
    {
        $data = ['Name' => 'test'];

        $packageBuilder = $this->createMock(PackageBuilder::class);
        $packageBuilder->expects($this->once())->method('buildPackage')->with($data, true);

        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->with(PackageBuilder::class)->willReturn($packageBuilder);

        $model = new PackageManager($serviceManager);
        $model->buildPackage($data, true);
    }

    public function testDelete()
    {
        $storage = $this->createMock('Model\Package\Storage\Direct');
        $storage->expects($this->once())->method('cleanup')->with('1415958319');

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            [Direct::class, $storage],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [Packages::class, static::$serviceManager->get(Packages::class)],
        ]);

        $model = new PackageManager($serviceManager);
        $model->deletePackage('package1');

        $connection = $this->getConnection();
        $dataset = $this->loadDataSet('Delete');
        $this->assertTablesEqual(
            $dataset->getTable('download_available'),
            $connection->createQueryTable('download_available', 'SELECT * FROM download_available ORDER BY fileid')
        );
        $this->assertTablesEqual(
            $dataset->getTable('download_enable'),
            $connection->createQueryTable(
                'download_enable',
                'SELECT id, fileid, info_loc, pack_loc FROM download_enable ORDER BY fileid'
            )
        );
        $this->assertTablesEqual(
            $dataset->getTable('devices'),
            $connection->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue FROM devices ORDER BY ivalue, name'
            )
        );
    }

    public function testDeleteException()
    {
        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage("Package 'invalid' does not exist");
        $model = $this->getModel();
        $model->deletePackage('invalid');
    }

    public function testUpdatePackage()
    {
        $newPackageData = array('Name' => 'new_name');

        $newPackage = $this->createMock('Model\Package\Package');
        $newPackage->method('offsetGet')->with('Id')->willReturn('new_id');

        /** @var Stub|Package */
        $package = $this->createStub(Package::class);
        $package->method('offsetGet')->willReturnMap([['Id', 'old_id'], ['Name', 'old_name']]);

        $model = $this->createPartialMock(
            PackageManager::class,
            ['buildPackage', 'getPackage', 'updateAssignments', 'deletePackage']
        );
        $model->expects($this->once())->method('buildPackage')->with($newPackageData, true);
        $model->method('getPackage')->with('new_name')->willReturn($newPackage);
        $model->expects($this->once())->method('updateAssignments')->with('old_id', 'new_id', 'p', 'r', 's', 'e', 'g');
        $model->expects($this->once())->method('deletePackage')->with('old_name');

        $model->updatePackage($package, $newPackageData, true, 'p', 'r', 's', 'e', 'g');
    }

    public function testUpdateAssignmentsNoActionRequired()
    {
        $this->getModel()->updateAssignments(1415958319, 3, false, false, false, false, false);

        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testUpdateAssignmentsNoMatch()
    {
        $this->getModel()->updateAssignments(1415958320, 3, false, true, false, false, false);

        $this->assertTablesEqual(
            $this->loadDataSet()->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function updateAssignmentsProvider()
    {
        return array(
            array('UpdateNoFilters', true, true, true, true, true),
            array('UpdatePending', true, false, false, false, false),
            array('UpdateRunning', false, true, false, false, false),
            array('UpdateSuccess', false, false, true, false, false),
            array('UpdateError', false, false, false, true, false),
            array('UpdateGroups', false, false, false, false, true),
            array('UpdateCombined', true, false, true, true, false),
        );
    }

    /**
     * Test updateAssignments() with various filters
     * @dataProvider updateAssignmentsProvider
     */
    public function testUpdateAssignments(
        $datasetName,
        $deployPending,
        $deployRunning,
        $deploySuccess,
        $deployError,
        $deployGroups
    ) {
        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            ['Library\Now', new DateTime('2015-02-08 14:17:29')],
            [ClientConfig::class, static::$serviceManager->get(ClientConfig::class)],
            [GroupInfo::class, static::$serviceManager->get(GroupInfo::class)],
        ]);

        $model = new PackageManager($serviceManager);
        $model->updateAssignments(
            1415958319,
            3,
            $deployPending,
            $deployRunning,
            $deploySuccess,
            $deployError,
            $deployGroups
        );

        $dataset = $this->loadDataSet($datasetName);
        $this->assertTablesEqual(
            $dataset->getTable('devices'),
            $this->getConnection()->createQueryTable(
                'devices',
                'SELECT hardware_id, name, ivalue, tvalue, comments FROM devices ORDER BY hardware_id, name, ivalue'
            )
        );
    }

    public function testUpdateAssignmentsException()
    {
        $this->expectException('Model\Package\RuntimeException');
        $this->expectExceptionMessage('database error');
        $data = array('Timestamp' => new \DateTime('@1415958319'));
        $clientConfig = $this->createMock('Database\Table\ClientConfig');
        $clientConfig->method('getSql')->will($this->throwException(new \RuntimeException('database error')));

        /** @var MockObject|ServiceManager */
        $serviceManager = $this->createMock(ServiceManager::class);
        $serviceManager->method('get')->willReturnMap([
            [ClientConfig::class, $clientConfig],
            [GroupInfo::class, static::$serviceManager->get(GroupInfo::class)],
            ['Library\Now', static::$serviceManager->get('Library\Now')],
        ]);

        $model = new PackageManager($serviceManager);
        $model->updateAssignments(1, 2, true, true, true, true, true);
    }
}
