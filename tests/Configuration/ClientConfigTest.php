<?php

namespace Braintacle\Test\Configuration;

use AssertionError;
use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Model\Client\Client;
use Model\Config;
use Model\Group\Group;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientConfig::class)]
class ClientConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function createClientConfig(?Config $config = null): ClientConfig
    {
        return new ClientConfig($config ?? $this->createStub(Config::class));
    }

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

        $options = $this->createClientConfig()->getOptions($client);
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

        $options = $this->createClientConfig()->getOptions($group);
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

        $options = $this->createClientConfig()->getDefaults($object);
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

    public static function getEffectiveConfigProvider()
    {
        return [
            ['contactInterval', 1, null, 1],
            ['contactInterval', 1, 2, 2],
            ['contactInterval', 2, 1, 1],
            ['downloadPeriodDelay', 1, null, 1],
            ['downloadPeriodDelay', 1, 2, 2],
            ['downloadPeriodDelay', 2, 1, 1],
            ['downloadCycleDelay', 1, null, 1],
            ['downloadCycleDelay', 1, 2, 2],
            ['downloadCycleDelay', 2, 1, 1],
            ['downloadFragmentDelay', 1, null, 1],
            ['downloadFragmentDelay', 1, 2, 2],
            ['downloadFragmentDelay', 2, 1, 1],
            ['downloadMaxPriority', 1, null, 1],
            ['downloadMaxPriority', 1, 2, 2],
            ['downloadMaxPriority', 2, 1, 1],
            ['downloadTimeout', 1, null, 1],
            ['downloadTimeout', 1, 2, 2],
            ['downloadTimeout', 2, 1, 1],
            ['packageDeployment', 0, 0, false],
            ['packageDeployment', 0, null, false],
            ['packageDeployment', 1, 0, false],
            ['packageDeployment', 1, null, true],
            ['allowScan', 0, 0, false],
            ['allowScan', 0, null, false],
            ['allowScan', 1, 0, false],
            ['allowScan', 1, null, true],
            ['scanSnmp', 0, 0, false],
            ['scanSnmp', 0, null, false],
            ['scanSnmp', 1, 0, false],
            ['scanSnmp', 1, null, true],
        ];
    }

    #[DataProvider('getEffectiveConfigProvider')]
    public function testGetEffectiveConfig(
        string $option,
        int $defaultValue,
        ?int $clientValue,
        int | bool $expectedValue,
    ) {
        $client = $this->createPartialMock(Client::class, ['getDefaultConfig', 'getConfig']);
        $client->method('getDefaultConfig')->willReturnCallback(
            fn($arg) => ($arg === $option) ? $defaultValue : 'ignore'
        );
        $client->method('getConfig')->willReturnCallback(fn($arg) => ($arg === $option) ? $clientValue : 'ignore');

        $effectiveConfig = $this->createClientConfig()->getEffectiveConfig($client);

        $this->assertSame($expectedValue, $effectiveConfig[$option]);
    }

    public static function getEffectiveConfigForInventoryIntervalProvider()
    {
        return [
            [-1, [1], 1, -1], // global value -1 always precedes
            [0, [-1], -1, 0], // global value 0 always precedes
            [1, [2, null], 3, 2], // smallest value from groups/client
            [1, [3, null], 2, 2], // smallest value from groups/client
            [1, [], null, 1], // no values defined, fall back to global value
            [1, [], 2, 2], // smallest value from groups/client
            [1, [2, 3], null, 2], // no client value, use smallest group value
            [1, [0], -1, -1], // client value overrides default
            [1, [-1], 0, -1], // client value does not override default
        ];
    }

    #[DataProvider('getEffectiveConfigForInventoryIntervalProvider')]
    public function testGetEffectiveConfigForInventoryInterval(
        int $globalValue,
        array $groupValues,
        ?int $clientValue,
        int $expectedValue,
    ) {
        $config = $this->createMock(Config::class);
        $config->method('__get')->with('inventoryInterval')->willReturn($globalValue);

        $groups = [];
        foreach ($groupValues as $groupValue) {
            $group = $this->createMock(Group::class);
            $group->method('getConfig')->with('inventoryInterval')->willReturn($groupValue);
            $groups[] = $group;
        }

        $client = $this->createPartialMock(Client::class, ['getConfig', 'getDefaultConfig', 'getGroups']);
        $client->method('getConfig')->willReturnCallback(
            fn($arg) => ($arg === 'inventoryInterval') ? $clientValue : 'ignore'
        );
        $client->method('getDefaultConfig')->willReturn('ignore');
        $client->method('getGroups')->willReturn($groups);

        $effectiveConfig = $this->createClientConfig($config)->getEffectiveConfig($client);

        $this->assertSame($expectedValue, $effectiveConfig['inventoryInterval']);
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
        $this->createClientConfig()->setOptions(
            $this->createStub($objectClass),
            $this->createStub($configClass)
        );
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

        $this->createClientConfig()->setOptions($object, $config);
    }
}
