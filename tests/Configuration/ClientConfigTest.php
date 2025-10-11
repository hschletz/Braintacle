<?php

namespace Braintacle\Test\Configuration;

use AssertionError;
use Braintacle\Client\Clients;
use Braintacle\Client\Configuration\ClientConfigurationParameters;
use Braintacle\Configuration\ClientConfig;
use Braintacle\Database\Migration;
use Braintacle\Database\Migrations;
use Braintacle\Database\Table;
use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Braintacle\Group\Group;
use Braintacle\Test\DatabaseConnection;
use Doctrine\DBAL\Connection;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use Model\Client\Client;
use Model\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClientConfig::class)]
#[UsesClass(Migration::class)]
#[UsesClass(Migrations::class)]
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

    private function createClientConfig(
        ?Clients $clients = null,
        ?Config $config = null,
        ?Connection $connection = null,
    ): ClientConfig {
        return new ClientConfig(
            $clients ?? $this->createStub(Clients::class),
            $config ?? $this->createStub(Config::class),
            $connection ?? $this->createStub(Connection::class),
        );
    }

    private function createClientConfigMock(
        array $methods,
        ?Clients $clients = null,
        ?Config $config = null,
        ?Connection $connection = null,
    ): MockObject | ClientConfig {
        return $this->getMockBuilder(ClientConfig::class)->onlyMethods($methods)->setConstructorArgs([
            $clients ?? $this->createStub(Clients::class),
            $config ?? $this->createStub(Config::class),
            $connection ?? $this->createStub(Connection::class),
        ])->getMock();
    }

    public function testGetOptionsForClient()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;

        $clientConfig = $this->createClientConfigMock(['getOption']);
        $clientConfig->method('getOption')->willReturnMap([
            [42, 'contactInterval', null],
            [42, 'inventoryInterval', -1],
            [42, 'packageDeployment', null],
            [42, 'downloadPeriodDelay', 2],
            [42, 'downloadCycleDelay', 3],
            [42, 'downloadFragmentDelay', 4],
            [42, 'downloadMaxPriority', 5],
            [42, 'downloadTimeout', 6],
            [42, 'allowScan', null],
            [42, 'scanSnmp', false],
            [42, 'scanThisNetwork', '192.0.2.0'],
        ]);

        $options = $clientConfig->getOptions($client);
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
        $group->id = 42;

        $clientConfig = $this->createClientConfigMock(['getOption']);
        $clientConfig->method('getOption')->willReturnMap([
            [42, 'contactInterval', null],
            [42, 'inventoryInterval', -1],
            [42, 'packageDeployment', null],
            [42, 'downloadPeriodDelay', 2],
            [42, 'downloadCycleDelay', 3],
            [42, 'downloadFragmentDelay', 4],
            [42, 'downloadMaxPriority', 5],
            [42, 'downloadTimeout', 6],
            [42, 'allowScan', null],
            [42, 'scanSnmp', false],
        ]);

        $options = $clientConfig->getOptions($group);
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

        $groupIds = [];
        $map = [];
        foreach ($groupValues as $index => $groupValue) {
            $id = $index + 1;
            $map[] = [$id, $option, $groupValue];
            $groupIds[] = $id;
        }

        $client = $this->createStub(Client::class);

        $clients = $this->createMock(Clients::class);
        $clients->method('getGroupIds')->with($client)->willReturn($groupIds);

        $clientConfig = $this->createClientConfigMock(['getOption'], clients: $clients, config: $config);
        $clientConfig->method('getOption')->willReturnMap($map);

        $defaults = $clientConfig->getClientDefaults($client);
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

        $defaults = $this->createClientConfig(config: $config)->getGlobalDefaults();

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
        $client->id = 42;

        $defaults = self::Defaults;
        $defaults[$option] = $defaultValue;

        $clientConfig = $this->createClientConfigMock(['getClientDefaults', 'getOption']);
        $clientConfig->method('getClientDefaults')->with($client)->willReturn($defaults);
        $clientConfig->method('getOption')->willReturnMap([[42, $option, $clientValue]]);

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

        $groupIds = [];
        $map = [];
        foreach ($groupValues as $index => $groupValue) {
            $id = $index + 1;
            $groupIds[] = $id;
            $map[] = [$id, 'inventoryInterval', $groupValue];
        }

        $client = $this->createStub(Client::class);
        $client->id = 42;
        $map[] = [42, 'inventoryInterval', $clientValue];

        $clients = $this->createMock(Clients::class);
        $clients->method('getGroupIds')->willReturn($groupIds);

        $clientConfig = $this->createClientConfigMock(
            ['getClientDefaults', 'getOption'],
            clients: $clients,
            config: $config,
        );
        $clientConfig->method('getClientDefaults')->with($client)->willReturn(self::Defaults);
        $clientConfig->method('getOption')->willReturnMap($map);

        $effectiveConfig = $clientConfig->getEffectiveConfig($client);

        $this->assertSame($expectedValue, $effectiveConfig['inventoryInterval']);
    }

    public function testGetExplicitConfigWithNonNullValues()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;

        $clientConfig = $this->createClientConfigMock(['getOption']);
        $clientConfig->method('getOption')->willReturnMap([
            [42, 'contactInterval', 0],
            [42, 'inventoryInterval', 1],
            [42, 'downloadPeriodDelay', 2],
            [42, 'downloadCycleDelay', 3],
            [42, 'downloadFragmentDelay', 4],
            [42, 'downloadMaxPriority', 5],
            [42, 'downloadTimeout', 6],
            [42, 'scanThisNetwork', 'network'],
            // The following options can only be FALSE or NULL
            [42, 'packageDeployment', false],
            [42, 'allowScan', false],
            [42, 'scanSnmp', false],
        ]);

        $this->assertSame(
            [
                'contactInterval' => 0,
                'inventoryInterval' => 1,
                'packageDeployment' => false,
                'downloadPeriodDelay' => 2,
                'downloadCycleDelay' => 3,
                'downloadFragmentDelay' => 4,
                'downloadMaxPriority' => 5,
                'downloadTimeout' => 6,
                'allowScan' => false,
                'scanSnmp' => false,
                'scanThisNetwork' => 'network',
            ],
            $clientConfig->getExplicitConfig($client),
        );
    }

    public function testGetExplicitConfigWithNullValues()
    {
        $client = $this->createStub(Client::class);
        $client->id = 42;

        $clientConfig = $this->createClientConfigMock(['getOption']);
        $clientConfig
            ->expects($this->atLeastOnce())
            ->method('getOption')
            ->with(42, $this->anything())
            ->willReturn(null);

        $this->assertSame([], $clientConfig->getExplicitConfig($client));
    }

    public static function getOptionProvider()
    {
        return [
            [10, 'packageDeployment', false],
            [11, 'packageDeployment', null],
            [10, 'allowScan', false],
            [11, 'allowScan', null],
            [10, 'scanThisNetwork', '192.0.2.0'],
            [11, 'scanThisNetwork', null],
            [10, 'scanSnmp', false],
            [11, 'scanSnmp', null],
            [10, 'inventoryInterval', 23],
            [11, 'inventoryInterval', null],
        ];
    }

    #[DataProvider('getOptionProvider')]
    public function testGetOption(int $id, string $option, int | string | bool | null $expectedValue)
    {
        DatabaseConnection::with(function (Connection $connection) use ($id, $option, $expectedValue): void {
            DatabaseConnection::initializeTable(Table::ClientConfig, ['hardware_id', 'name', 'ivalue', 'tvalue'], [
                [10, 'DOWNLOAD_SWITCH', 0, null],
                [10, 'FREQUENCY', 23, null],
                [10, 'IPDISCOVER', 0, null],
                [10, 'IPDISCOVER', 2, '192.0.2.0'],
                [10, 'SNMP_SWITCH', 0, null],
                [11, 'DOWNLOAD_SWITCH', 1, null],
            ]);

            $config = $this->createMock(Config::class);
            $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

            $clientConfig = $this->createClientConfig(config: $config, connection: $connection);

            $this->assertSame($expectedValue, $clientConfig->getOption($id, $option));
        });
    }

    public static function setOptionProvider()
    {
        return [
            'regular delete' => [
                Group::class,
                10,
                'inventoryInterval',
                null,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ]
            ],
            'regular update' => [
                Group::class,
                10,
                'inventoryInterval',
                42,
                23,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 42, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'regular insert' => [
                Group::class,
                10,
                'contactInterval',
                42,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'PROLOG_FREQ', 42, null],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'package deployment enable' => [
                Group::class,
                10,
                'packageDeployment',
                true,
                0,
                [
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],

                ],
            ],
            'package deployment disable' => [
                Group::class,
                11,
                'packageDeployment',
                false,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 0, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'scan snmp enable' => [
                Group::class,
                10,
                'scanSnmp',
                true,
                0,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'scan snmp disable' => [
                Group::class,
                11,
                'scanSnmp',
                false,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 0, null],
                ],
            ],
            'scan snmp unset' => [
                Group::class,
                11,
                'scanSnmp',
                null,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                ],
            ],
            'allow scan enable' => [
                Group::class,
                10,
                'allowScan',
                true,
                0,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'allow scan disable' => [
                Group::class,
                11,
                'allowScan',
                false,
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 0, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
            'scan this network insert' => [
                Client::class,
                11,
                'scanThisNetwork',
                'addr',
                null,
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 2, 'addr'],
                    [11, 'SNMP_SWITCH', 1, null],

                ]
            ],
            'scan this network delete' => [
                Client::class,
                10,
                'scanThisNetwork',
                null,
                'addr',
                [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ],
            ],
        ];
    }

    /**
     * @param class-string<Client|Group> $class
     */
    #[DataProvider('setOptionProvider')]
    public function testSetOption(
        string $class,
        int $id,
        string $option,
        int | string | bool | null $value,
        int | string | null $oldValue,
        array $expectedContent,
    ) {
        DatabaseConnection::with(
            function (Connection $connection) use ($class, $id, $option, $value, $oldValue, $expectedContent) {
                $columns = ['hardware_id', 'name', 'ivalue', 'tvalue'];
                DatabaseConnection::initializeTable(Table::ClientConfig, $columns, [
                    [10, 'DOWNLOAD_SWITCH', 0, null],
                    [10, 'FREQUENCY', 23, null],
                    [10, 'IPDISCOVER', 0, null],
                    [10, 'IPDISCOVER', 2, '192.0.2.0'],
                    [10, 'SNMP_SWITCH', 0, null],
                    [11, 'DOWNLOAD_SWITCH', 1, null],
                    [11, 'IPDISCOVER', 1, null],
                    [11, 'SNMP_SWITCH', 1, null],
                ]);

                $config = $this->createMock(Config::class);
                $config->method('getDbIdentifier')->with($option)->willReturnMap([
                    ['contactInterval', 'PROLOG_FREQ'],
                    ['inventoryInterval', 'FREQUENCY'],
                    ['packageDeployment', 'DOWNLOAD'],
                    ['scanSnmp', 'SNMP'],
                ]);

                $object = $this->createMock($class);
                $object->id = $id;

                $clientConfig = $this->createClientConfigMock(['getOption'], config: $config, connection: $connection);
                $clientConfig->method('getOption')->with($id, $option)->willReturn($oldValue);
                $clientConfig->setOption($object, $option, $value);

                $content = $connection
                    ->createQueryBuilder()
                    ->select(...$columns)
                    ->from(Table::ClientConfig)
                    ->addOrderBy('hardware_id')
                    ->addOrderBy('name')
                    ->addOrderBy('ivalue')
                    ->fetchAllNumeric();

                $this->assertEquals($expectedContent, $content);
            }
        );
    }

    public function testSetOptionUnchanged()
    {
        $config = $this->createMock(Config::class);
        $config->method('getDbIdentifier')->with('inventoryInterval')->willReturn('FREQUENCY');

        $connection = $this->createMock(Connection::class);
        $connection->expects($this->never())->method('insert');
        $connection->expects($this->never())->method('update');
        $connection->expects($this->never())->method('delete');

        $client = $this->createMock(Client::class);
        $client->id = 10;

        $clientConfig = $this->createClientConfigMock(['getOption'], config: $config, connection: $connection);
        $clientConfig->expects($this->once())->method('getOption')->with(10, 'inventoryInterval')->willReturn(23);

        $clientConfig->setOption($client, 'inventoryInterval', 23);
    }

    public function testSetConfigRollbackOnException()
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->once())->method('rollBack');
        $connection->expects($this->never())->method('commit');
        $connection->method('delete')->willThrowException(new Exception('test message'));

        $client = $this->createStub(Client::class);
        $client->id = 42;

        $this->expectExceptionMessage('test message');

        $clientConfig = $this->createClientConfigMock(['getOption'], connection: $connection);
        $clientConfig->method('getOption')->willReturn(null);
        $clientConfig->setOption($client, 'allowScan', false);
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
    public function testSetOptions(
        string $objectClass,
        string $configClass,
        array $inputOptions,
        array $expectedOptions,
    ) {
        $object = $this->createStub($objectClass);

        /** @var Mock|ClientConfig */
        $clientConfig = Mockery::mock(ClientConfig::class)->makePartial();
        foreach ($expectedOptions as $option => $value) {
            $clientConfig->shouldReceive('setOption')->once()->with($object, $option, Mockery::isSame($value));
        }

        $config = new ($configClass);
        foreach ($inputOptions as $option => $value) {
            $config->$option = $value;
        }

        $clientConfig->setOptions($object, $config);
    }
}
