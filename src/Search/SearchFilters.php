<?php

namespace Braintacle\Search;

use Formotron\Validator;
use InvalidArgumentException;
use Laminas\Translator\TranslatorInterface;
use Model\Client\CustomFieldManager;
use Model\Registry\RegistryManager;
use Override;
use UnexpectedValueException;

/**
 * Search filter definitions.
 */
class SearchFilters implements Validator
{
    private array $filters;
    private array $types;

    public function __construct(
        private TranslatorInterface $translator,
        private RegistryManager $registryManager,
        private CustomFieldManager $customFieldManager,
    ) {}

    private function initialize(): void
    {
        $this->filters = [
            'Name' => $this->translator->translate('Name'),
            'UserName' => $this->translator->translate('User name'),
            'Windows.UserDomain' => $this->translator->translate('User domain'),
            'Windows.Workgroup' => $this->translator->translate('Workgroup'),
            'OsName' => $this->translator->translate('Operating system'),
            'OsVersionNumber' => $this->translator->translate('OS version number'),
            'OsVersionString' => $this->translator->translate('OS version string'),
            'Windows.CpuArchitecture' => $this->translator->translate('OS architecture'),
            'OsComment' => $this->translator->translate('OS comment'),
            'Windows.ProductKey' => $this->translator->translate('Windows product key'),
            'Windows.ManualProductKey' => $this->translator->translate('Windows product key (manual)'),
            'Software.name' => $this->translator->translate('Software: Name'),
            'Software.version' => $this->translator->translate('Software: Version'),
            'Software.publisher' => $this->translator->translate('Software: Publisher'),
            'Software.comment' => $this->translator->translate('Software: Comment'),
            'Software.installLocation' => $this->translator->translate('Software: Install location'),
            'MsOfficeProduct.ProductKey' => $this->translator->translate('MS Office product key'),
            'MsOfficeProduct.ProductId' => $this->translator->translate('MS Office product ID'),
            'InventoryDate' => $this->translator->translate('Inventory date'),
            'LastContactDate' => $this->translator->translate('Last contact'),
            'CpuType' => $this->translator->translate('CPU type'),
            'CpuClock' => $this->translator->translate('CPU clock (MHz)'),
            'CpuCores' => $this->translator->translate('CPU cores'),
            'PhysicalMemory' => $this->translator->translate('Physical memory'),
            'SwapMemory' => $this->translator->translate('Swap'),
            'Manufacturer' => $this->translator->translate('Manufacturer'),
            'ProductName' => $this->translator->translate('Model'),
            'Serial' => $this->translator->translate('Serial number'),
            'AssetTag' => $this->translator->translate('Asset tag'),
            'BiosVersion' => $this->translator->translate('BIOS version'),
            'BiosDate' => $this->translator->translate('BIOS date'),
            'Filesystem.Size' => $this->translator->translate('Filesystem size (MB)'),
            'Filesystem.FreeSpace' => $this->translator->translate('Filesystem free space (MB)'),
            'DnsServer' => $this->translator->translate('DNS server'),
            'DefaultGateway' => $this->translator->translate('Default gateway'),
            'NetworkInterface.IpAddress' => $this->translator->translate('IP address'),
            'NetworkInterface.MacAddress' => $this->translator->translate('MAC address'),
            'NetworkInterface.Subnet' => $this->translator->translate('Network address'),
            'NetworkInterface.Netmask' => $this->translator->translate('Netmask'),
            'Printer.Name' => $this->translator->translate('Printer name'),
            'Printer.Port' => $this->translator->translate('Printer port'),
            'Printer.Driver' => $this->translator->translate('Printer driver'),
            'UserAgent' => $this->translator->translate('User agent'),
            'Display.Manufacturer' => $this->translator->translate('Monitor: manufacturer'),
            'Display.Description' => $this->translator->translate('Monitor: description'),
            'Display.Serial' => $this->translator->translate('Monitor: serial'),
            'Display.Edid' => $this->translator->translate('Monitor: EDID'),
            'DisplayController.Name' => $this->translator->translate('Display controller'),
            'DisplayController.Memory' => $this->translator->translate('GPU memory'),
            'Modem.Name' => $this->translator->translate('Modem'),
            'AudioDevice.Name' => $this->translator->translate('Audio device'),
            'Port.Name' => $this->translator->translate('Port name'),
        ];

        $this->types = [
            'CpuClock' => 'number',
            'CpuCores' => 'number',
            'InventoryDate' => 'date',
            'LastContactDate' => 'date',
            'PhysicalMemory' => 'number',
            'SwapMemory' => 'number',
            'Filesystem.Size' => 'number',
            'Filesystem.FreeSpace' => 'number',
        ];

        foreach ($this->registryManager->getValueDefinitions() as $regValue) {
            $name = $regValue->name;
            $this->filters["Registry.$name"] = "Registry: $name";
        }

        $template = $this->translator->translate('User defined: %s');
        foreach ($this->customFieldManager->getFields() as $name => $type) {
            $key = "CustomFields.$name";
            switch ($type) {
                case 'text':
                case 'clob':
                    break;
                case 'integer':
                case 'float':
                    $this->types[$key] = 'number';
                    break;
                case 'date':
                    $this->types[$key] = 'date';
                    break;
                default:
                    throw new UnexpectedValueException('Unsupported datatype: ' . $type);
            }
            if ($name == 'TAG') {
                $label = $this->translator->translate('Category');
            } else {
                $label = $name;
            }
            $this->filters[$key] = sprintf($template, $label);
        }
    }

    public function getFilters(): array
    {
        if (!isset($this->filters)) {
            $this->initialize();
        }

        return $this->filters;
    }

    public function getNonTextTypes(): array
    {
        if (!isset($this->types)) {
            $this->initialize();
        }

        return $this->types;
    }

    #[Override]
    public function validate(mixed $value, array $args): void
    {
        if (!isset($this->getFilters()[$value])) {
            throw new InvalidArgumentException('Invalid search filter: ' . $value);
        }
    }
}
