<?php

namespace Braintacle\Test\Configuration;

use AssertionError;
use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientConfig::class)]
class ClientConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetOptionsForClient()
    {
        $client = $this->createStub(Client::class);
        $client->method('getConfig')->willReturnMap([
            ['contactInterval', null],
            ['inventoryInterval', -1],
            ['packageDeployment', null],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['allowScan', null],
            ['scanSnmp', 0],
            ['scanThisNetwork', '192.0.2.0'],
        ]);

        $options = (new ClientConfig())->getOptions($client);
        $this->assertSame(
            [
                'contactInterval' => null,
                'inventoryInterval' => -1,
                'packageDeployment' => true,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => true,
                'scanSnmp' => false,
                'scanThisNetwork' => '192.0.2.0',
            ],
            $options,
        );
    }

    public function testGetOptionsForGroup()
    {
        $group = $this->createStub(Group::class);
        $group->method('getConfig')->willReturnMap([
            ['contactInterval', null],
            ['inventoryInterval', -1],
            ['packageDeployment', null],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['allowScan', null],
            ['scanSnmp', 0],
        ]);

        $options = (new ClientConfig())->getOptions($group);
        $this->assertSame(
            [
                'contactInterval' => null,
                'inventoryInterval' => -1,
                'packageDeployment' => true,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => true,
                'scanSnmp' => false,
            ],
            $options,
        );
    }

    public static function getDefaultsProvider()
    {
        return [
            [Client::class],
            [Group::class],
        ];
    }

    /**
     * @param class-string<Client|Group> $class
     */
    #[DataProvider('getDefaultsProvider')]
    public function testGetDefaults(string $class)
    {
        $object = $this->createStub($class);
        $object->method('getDefaultConfig')->willReturnMap([
            ['contactInterval', null],
            ['inventoryInterval', -1],
            ['packageDeployment', 1],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['allowScan', 1],
            ['scanSnmp', 0],
        ]);

        $options = (new ClientConfig())->getDefaults($object);
        $this->assertSame(
            [
                'contactInterval' => null,
                'inventoryInterval' => -1,
                'packageDeployment' => true,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => true,
                'scanSnmp' => false,
            ],
            $options,
        );
    }

    public function testGetEffectiveConfig()
    {
        $class = $this->createStub(Client::class);
        $class->method('getEffectiveConfig')->willReturnMap([
            ['contactInterval', null],
            ['inventoryInterval', -1],
            ['packageDeployment', 1],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['allowScan', 1],
            ['scanSnmp', 0],
        ]);

        $options = (new ClientConfig())->getEffectiveConfig($class);
        $this->assertSame(
            [
                'contactInterval' => null,
                'inventoryInterval' => -1,
                'packageDeployment' => true,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => true,
                'scanSnmp' => false,
            ],
            $options,
        );
    }

    public static function setOptionsInvalidArgumentsProvider()
    {
        return [
            [Client::class, GroupConfigurationParameters::class],
            [Group::class, ClientConfigurationParameters::class],
        ];
    }

    /**
     * @param class-string<Client|Group> $objectClass
     * @param class-string<ClientConfigurationParameters|GroupConfigurationParameters> $configClass
     */
    #[DataProvider('setOptionsInvalidArgumentsProvider')]
    public function testSetOptionsInvalidArgument(string $objectClass, string $configClass)
    {
        $this->expectException(AssertionError::class);
        (new ClientConfig())->setOptions($this->createStub($objectClass), $this->createStub($configClass));
    }

    public static function setOptionsProvider()
    {
        return [
            'client with package deployment disabled' => [
                Client::class,
                ClientConfigurationParameters::class,
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => false,
                    'downloadPeriodDelay' => 2,
                    'downloadCycleDelay' => 3,
                    'downloadFragmentDelay' => 4,
                    'downloadMaxPriority' => 5,
                    'downloadTimeout' => 6,
                    'allowScan' => true,
                    'scanSnmp' => false,
                    'scanThisNetwork' => '192.0.2.0',
                ],
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => false,
                    'downloadPeriodDelay' => null,
                    'downloadCycleDelay' => null,
                    'downloadFragmentDelay' => null,
                    'downloadMaxPriority' => null,
                    'downloadTimeout' => null,
                    'allowScan' => true,
                    'scanSnmp' => false,
                    'scanThisNetwork' => '192.0.2.0',
                ],
            ],
            'client with network scanning disabled' => [
                Client::class,
                ClientConfigurationParameters::class,
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => true,
                    'downloadPeriodDelay' => 2,
                    'downloadCycleDelay' => 3,
                    'downloadFragmentDelay' => 4,
                    'downloadMaxPriority' => 5,
                    'downloadTimeout' => 6,
                    'allowScan' => false,
                    'scanSnmp' => false,
                    'scanThisNetwork' => '192.0.2.0',
                ],
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => true,
                    'downloadPeriodDelay' => 2,
                    'downloadCycleDelay' => 3,
                    'downloadFragmentDelay' => 4,
                    'downloadMaxPriority' => 5,
                    'downloadTimeout' => 6,
                    'allowScan' => false,
                    'scanSnmp' => null,
                    'scanThisNetwork' => null,
                ],
            ],
            'group' => [
                Group::class,
                GroupConfigurationParameters::class,
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => true,
                    'downloadPeriodDelay' => 2,
                    'downloadCycleDelay' => 3,
                    'downloadFragmentDelay' => 4,
                    'downloadMaxPriority' => 5,
                    'downloadTimeout' => 6,
                    'allowScan' => true,
                    'scanSnmp' => false,
                ],
                [
                    'contactInterval' => null,
                    'inventoryInterval' => -1,
                    'packageDeployment' => true,
                    'downloadPeriodDelay' => 2,
                    'downloadCycleDelay' => 3,
                    'downloadFragmentDelay' => 4,
                    'downloadMaxPriority' => 5,
                    'downloadTimeout' => 6,
                    'allowScan' => true,
                    'scanSnmp' => false,
                ],
            ],
        ];
    }

    /**
     * @param class-string<Client|Group> $objectClass
     * @param class-string<ClientConfigurationParameters|GroupConfigurationParameters> $configClass
     */
    #[DataProvider('setOptionsProvider')]
    #[DoesNotPerformAssertions]
    public function testSetOptions(
        string $objectClass,
        string $configClass,
        array $inputOptions,
        array $expectedOptions,
    ) {
        $object = Mockery::mock($objectClass);
        foreach ($expectedOptions as $option => $value) {
            $object->shouldReceive('setConfig')->with($option, Mockery::isSame($value));
        }

        $config = new ($configClass);
        foreach ($inputOptions as $option => $value) {
            $config->$option = $value;
        }

        (new ClientConfig())->setOptions($object, $config);
    }
}
