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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientConfig::class)]
class ClientConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const Defaults = [
        'contactInterval' => 'ignore',
        'inventoryInterval' => 'ignore',
        'packageDeployment' => 'ignore',
        'downloadPeriodDelay' => 'ignore',
        'downloadCycleDelay' => 'ignore',
        'downloadFragmentDelay' => 'ignore',
        'downloadMaxPriority' => 'ignore',
        'downloadTimeout' => 'ignore',
        'allowScan' => 'ignore',
        'scanSnmp' => 'ignore',
    ];

    private function createClientConfig(?Config $config = null): ClientConfig
    {
        return new ClientConfig($config ?? $this->createStub(Config::class));
    }

    private function createClientConfigMock(array $methods, ?Config $config = null): MockObject | ClientConfig
    {
        return $this->getMockBuilder(ClientConfig::class)->onlyMethods($methods)->setConstructorArgs([
            $config ?? $this->createStub(Config::class),
        ])->getMock();
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

    public static function getClientDefaultsProvider()
    {
        // All options have a default, so the global value can never be NULL.
        return [
            ['inventoryInterval', -1, [0], -1], // global value -1 precedes
            ['inventoryInterval', 0, [-1], 0], // global value 0 precedes
            ['inventoryInterval', 1, [], 1], // no group values, default to global value
            ['inventoryInterval', 1, [null], 1], // no group values, default to global value
            ['inventoryInterval', 1, [2, null, 3], 2], // smallest group value
            ['inventoryInterval', 4, [2, 3, null], 2], // smallest group value
            ['contactInterval', 1, [2, 3, null], 2],
            ['contactInterval', 1, [], 1],
            ['downloadMaxPriority', 1, [2, 3, null], 2],
            ['downloadMaxPriority', 1, [], 1],
            ['downloadTimeout', 1, [2, 3, null], 2],
            ['downloadTimeout', 1, [], 1],
            ['downloadPeriodDelay', 3, [1, 2, null], 2],
            ['downloadPeriodDelay', 1, [], 1],
            ['downloadCycleDelay', 3, [1, 2, null], 2],
            ['downloadCycleDelay', 1, [], 1],
            ['downloadFragmentDelay', 3, [1, 2, null], 2],
            ['downloadFragmentDelay', 1, [], 1],
            ['packageDeployment', 0, [1], false],
            ['packageDeployment', 1, [], true],
            ['packageDeployment', 1, [null, 1], true],
            ['packageDeployment', 1, [0, 1], false],
            ['scanSnmp', 0, [1], false],
            ['scanSnmp', 1, [], true],
            ['scanSnmp', 1, [null, 1], true],
            ['scanSnmp', 1, [0, 1], false],
            ['allowScan', 0, [1], false],
            ['allowScan', 1, [], true],
            ['allowScan', 2, [null, 1], true],
            ['allowScan', 2, [0, 1], false],
        ];
    }

    #[DataProvider('getClientDefaultsProvider')]
    public function testGetClientDefaults(
        string $option,
        int $globalValue,
        array $groupValues,
        int | bool $expectedValue,
    ) {
        $globalOption = (($option == 'allowScan') ? 'scannersPerSubnet' : $option);

        $config = $this->createMock(Config::class);
        $config->method('__get')->willReturnCallback(fn($arg) => ($arg == $globalOption) ? $globalValue : 'ignore');

        $groups = [];
        foreach ($groupValues as $groupValue) {
            $group = $this->createMock(Group::class);
            $group->method('getConfig')->willReturnCallback(fn($arg) => ($arg == $option) ? $groupValue : 'ignore');
            $groups[] = $group;
        }

        $client = $this->createStub(Client::class);
        $client->method('getGroups')->willReturn($groups);

        $defaults = $this->createClientConfig($config)->getClientDefaults($client);
        $this->assertSame($expectedValue, $defaults[$option]);
    }

    public static function getGroupDefaultsProvider()
    {
        return [
            ['contactInterval', 1, 'contactInterval', 1],
            ['packageDeployment', false, 'packageDeployment', 0],
            ['allowScan', false, 'scannersPerSubnet', 0],
            ['allowScan', true, 'scannersPerSubnet', 2],
            ['scanSnmp', true, 'scannersPerSubnet', 1],
        ];
    }

    #[DataProvider('getGroupDefaultsProvider')]
    public function testGetGroupDefaults(
        string $option,
        int|bool $expectedValue,
        string $globalOptionName,
        int $globalOptionValue,
    ) {
        $config = $this->createMock(Config::class);
        $config->method('__get')->willReturnCallback(
            fn($arg) => ($arg == $globalOptionName) ? $globalOptionValue : 'ignore'
        );

        $defaults = $this->createClientConfig($config)->getGlobalDefaults();

        $this->assertSame($expectedValue, $defaults[$option]);
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
            ['packageDeployment', false, 0, false],
            ['packageDeployment', false, null, false],
            ['packageDeployment', true, 0, false],
            ['packageDeployment', true, null, true],
            ['allowScan', false, 0, false],
            ['allowScan', false, null, false],
            ['allowScan', true, 0, false],
            ['allowScan', true, null, true],
            ['scanSnmp', false, 0, false],
            ['scanSnmp', false, null, false],
            ['scanSnmp', true, 0, false],
            ['scanSnmp', true, null, true],
        ];
    }

    #[DataProvider('getEffectiveConfigProvider')]
    public function testGetEffectiveConfig(
        string $option,
        int | bool $defaultValue,
        ?int $clientValue,
        int | bool $expectedValue,
    ) {
        $client = $this->createStub(Client::class);
        $client->method('getConfig')->willReturnCallback(fn($arg) => ($arg === $option) ? $clientValue : 'ignore');

        $defaults = self::Defaults;
        $defaults[$option] = $defaultValue;

        $clientConfig = $this->createClientConfigMock(['getClientDefaults']);
        $clientConfig->method('getClientDefaults')->with($client)->willReturn($defaults);

        $effectiveConfig = $clientConfig->getEffectiveConfig($client);

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

        $client = $this->createStub(Client::class);
        $client->method('getConfig')->willReturnCallback(
            fn($arg) => ($arg === 'inventoryInterval') ? $clientValue : 'ignore'
        );
        $client->method('getGroups')->willReturn($groups);

        $clientConfig = $this->createClientConfigMock(['getClientDefaults'], $config);
        $clientConfig->method('getClientDefaults')->with($client)->willReturn(self::Defaults);

        $effectiveConfig = $clientConfig->getEffectiveConfig($client);

        $this->assertSame($expectedValue, $effectiveConfig['inventoryInterval']);
    }

    public function testGetExplicitConfigWithNonNullValues()
    {
        $client = $this->createStub(Client::class);
        $client->method('getConfig')->willReturnMap([
            ['contactInterval', 0],
            ['inventoryInterval', 1],
            ['downloadPeriodDelay', 2],
            ['downloadCycleDelay', 3],
            ['downloadFragmentDelay', 4],
            ['downloadMaxPriority', 5],
            ['downloadTimeout', 6],
            ['scanThisNetwork', 'network'],
            // The following options can only be 0 or NULL
            ['packageDeployment', 0],
            ['allowScan', 0],
            ['scanSnmp', 0],
        ]);

        $this->assertSame(
            [
                'contactInterval' => 0,
                'inventoryInterval' => 1,
                'packageDeployment' => 0,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => 0,
                'scanSnmp' => 0,
                'scanThisNetwork' => 'network',
            ],
            $this->createClientConfig()->getExplicitConfig($client),
        );
    }

    public function testGetExplicitConfigWithNullValues()
    {
        $client = $this->createStub(Client::class);
        $client->method('getConfig')->willReturn(null);

        $this->assertSame([], $this->createClientConfig()->getExplicitConfig($client));
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
