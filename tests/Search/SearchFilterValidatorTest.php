<?php

namespace Braintacle\Test\Search;

use ArrayIterator;
use Braintacle\Search\SearchFilterValidator;
use EmptyIterator;
use Formotron\AssertionFailedException;
use Model\Client\CustomFieldManager;
use Model\Registry\RegistryManager;
use Model\Registry\Value as RegistryValue;
use PHPUnit\Framework\TestCase;

class SearchFilterValidatorTest extends TestCase
{
    public function testCustomFieldsValid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn(['fieldName' => 'text']);

        $registryManager = $this->createStub(RegistryManager::class);

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);
        $this->assertEquals([], $validator->getValidationErrors('CustomFields.fieldName', []));
    }

    public function testCustomFieldsInvalid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $customFieldManager->method('getFields')->willReturn([]);

        $registryManager = $this->createStub(RegistryManager::class);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid search filter: CustomFields.fieldName');

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);
        $validator->getValidationErrors('CustomFields.fieldName', []);
    }

    public function testRegistryValid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);

        $registryValue = new RegistryValue();
        $registryValue->name = '_name';

        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new ArrayIterator([$registryValue]));

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);
        $this->assertEquals([], $validator->getValidationErrors('Registry._name', []));
    }

    public function testRegistryInvalid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);

        $registryManager = $this->createStub(RegistryManager::class);
        $registryManager->method('getValueDefinitions')->willReturn(new EmptyIterator());

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid search filter: Registry._name');

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);
        $validator->getValidationErrors('Registry._name', []);
    }

    public function testOtherFilterValid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $registryManager = $this->createStub(RegistryManager::class);

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);
        $this->assertEquals([], $validator->getValidationErrors('Filesystem.Size', []));
    }

    public function testOtherFilterInvalid()
    {
        $customFieldManager = $this->createStub(CustomFieldManager::class);
        $registryManager = $this->createStub(RegistryManager::class);

        $validator = new SearchFilterValidator($customFieldManager, $registryManager);

        $this->expectException(AssertionFailedException::class);
        $this->expectExceptionMessage('Invalid search filter: invalid');

        $validator->getValidationErrors('invalid', []);
    }
}
