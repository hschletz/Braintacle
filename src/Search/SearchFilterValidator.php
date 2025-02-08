<?php

namespace Braintacle\Search;

use Formotron\AssertionFailedException;
use Formotron\Validator;
use Model\Client\CustomFieldManager;
use Model\Registry\RegistryManager;

/**
 * Validate search filter type.
 */
class SearchFilterValidator implements Validator
{
    private static array $staticNames = [
        'AssetTag',
        'AudioDevice.Name',
        'BiosDate',
        'BiosVersion',
        'CpuClock',
        'CpuCores',
        'CpuType',
        'DefaultGateway',
        'Display.Description',
        'Display.Edid',
        'Display.Manufacturer',
        'Display.Serial',
        'DisplayController.Memory',
        'DisplayController.Name',
        'DnsServer',
        'Filesystem.FreeSpace',
        'Filesystem.Size',
        'InventoryDate',
        'LastContactDate',
        'Name',
        'Manufacturer',
        'Modem.Name',
        'MsOfficeProduct.ProductId',
        'MsOfficeProduct.ProductKey',
        'NetworkInterface.IpAddress',
        'NetworkInterface.MacAddress',
        'NetworkInterface.Netmask',
        'NetworkInterface.Subnet',
        'OsComment',
        'OsName',
        'OsVersionNumber',
        'OsVersionString',
        'PhysicalMemory',
        'Port.Name',
        'Printer.Driver',
        'Printer.Name',
        'Printer.Port',
        'ProductName',
        'Serial',
        'Software.comment',
        'Software.installLocation',
        'Software.name',
        'Software.publisher',
        'Software.version',
        'SwapMemory',
        'UserAgent',
        'UserName',
        'Windows.CpuArchitecture',
        'Windows.ManualProductKey',
        'Windows.ProductKey',
        'Windows.UserDomain',
        'Windows.Workgroup',
    ];

    public function __construct(
        private CustomFieldManager $customFieldManager,
        private RegistryManager $registryManager,
    ) {
    }

    public function getValidationErrors(mixed $value, array $args): array
    {
        if (str_starts_with($value, 'CustomFields')) {
            foreach (array_keys($this->customFieldManager->getFields()) as $name) {
                if ($value == 'CustomFields.' . $name) {
                    return [];
                }
            }
        }
        if (str_starts_with($value, 'Registry.')) {
            foreach ($this->registryManager->getValueDefinitions() as $regValue) {
                if ($value == 'Registry.' . $regValue->name) {
                    return [];
                }
            }
        }
        if (in_array($value, self::$staticNames)) {
            return [];
        }

        throw new AssertionFailedException('Invalid search filter: ' . $value);
    }
}
