<?php

namespace Braintacle\Test\Search;

use ArrayIterator;
use Braintacle\Search\SearchFilters;
use Braintacle\Test\TranslatorStubTrait;
use EmptyIterator;
use InvalidArgumentException;
use Laminas\Translator\TranslatorInterface;
use Model\Client\CustomFieldManager;
use Model\Registry\RegistryManager;
use Model\Registry\Value as RegistryValue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(SearchFilters::class)]
class SearchFiltersTest extends TestCase
{
    use TranslatorStubTrait;

    private function createSearchFilters(
        ?TranslatorInterface $translator = null,
        ?RegistryManager $registryManager = null,
        ?CustomFieldManager $customFieldManager = null,
    ): SearchFilters {
        if (!$customFieldManager) {
            $customFieldManager = $this->createStub(CustomFieldManager::class);
            $customFieldManager->method('getFields')->willReturn([]);
        }
        if (!$registryManager) {
            $registryManager = $this->createStub(RegistryManager::class);
            $registryManager->method('getValueDefinitions')->willReturn([]);
        }

        return new SearchFilters(
            $translator ?? $this->createTranslatorStub(),
            $registryManager,
            $customFieldManager,
        );
    }

    public function testGetFilters()
    {
        $registryValue = new RegistryValue();
        $registryValue->name = '_name';

        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new ArrayIterator([$registryValue]));

        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn([
            'TAG' => 'text',
            'fieldName' => 'text',
        ]);

        $searchFilters = $this->createSearchFilters(
            registryManager: $registryManager,
            customFieldManager: $customFieldManager,
        );

        $filters = $searchFilters->getFilters();
        $this->assertEquals('_User name', $filters['UserName']);
        $this->assertEquals('Registry: _name', $filters['Registry._name']);
        $this->assertEquals('_User defined: _Category', $filters['CustomFields.TAG']);
        $this->assertEquals('_User defined: fieldName', $filters['CustomFields.fieldName']);
    }

    public function testGetNonTextTypes()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn([
            '_text' => 'text',
            '_clob' => 'clob',
            '_integer' => 'integer',
            '_float' => 'float',
            '_date' => 'date',
        ]);

        $searchFilters = $this->createSearchFilters(customFieldManager: $customFieldManager);
        $types = $searchFilters->getNonTextTypes();

        $this->assertEquals(['number', 'date'], array_values(array_unique($types)));
        $this->assertEquals('number', $types['CpuClock']);
        $this->assertEquals('number', $types['CustomFields._integer']);
        $this->assertEquals('number', $types['CustomFields._float']);
        $this->assertEquals('date', $types['CustomFields._date']);
        $this->assertFalse(array_key_exists('CustomFields._text', $types));
        $this->assertFalse(array_key_exists('CustomFields._clob', $types));
    }

    public function testGetNonTextTypesInvalid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn(['key' => 'invalid']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unsupported datatype: invalid');

        $searchFilters = $this->createSearchFilters(customFieldManager: $customFieldManager);
        $searchFilters->getNonTextTypes();
    }

    #[DoesNotPerformAssertions]
    public function testValidateCustomFieldsValid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn(['fieldName' => 'text']);

        $searchFilters = $this->createSearchFilters(customFieldManager: $customFieldManager);
        $searchFilters->validate('CustomFields.fieldName', []);
    }

    public function testValidateCustomFieldsInvalid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn([]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid search filter: CustomFields.fieldName');

        $searchFilters = $this->createSearchFilters(customFieldManager: $customFieldManager);
        $searchFilters->validate('CustomFields.fieldName', []);
    }

    #[DoesNotPerformAssertions]
    public function testValidateRegistryValid()
    {
        $registryValue = new RegistryValue();
        $registryValue->name = '_name';

        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new ArrayIterator([$registryValue]));

        $searchFilters = $this->createSearchFilters(registryManager: $registryManager);
        $searchFilters->validate('Registry._name', []);
    }

    public function testValidateRegistryInvalid()
    {
        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new EmptyIterator());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid search filter: Registry._name');

        $searchFilters = $this->createSearchFilters(registryManager: $registryManager);
        $searchFilters->validate('Registry._name', []);
    }

    #[DoesNotPerformAssertions]
    public function testValidateOtherFilterValid()
    {
        $searchFilters = $this->createSearchFilters();
        $searchFilters->validate('Filesystem.Size', []);
    }

    public function testValidateOtherFilterInvalid()
    {
        $searchFilters = $this->createSearchFilters();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid search filter: invalid');

        $searchFilters->validate('invalid', []);
    }
}
