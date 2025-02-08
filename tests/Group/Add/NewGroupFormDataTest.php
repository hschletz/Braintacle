<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\NewGroupFormData;
use Braintacle\Group\Membership;
use Braintacle\Search\SearchFilterValidator;
use Braintacle\Search\SearchOperator;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Transformer\ToBool;
use Braintacle\Transformer\ToBoolTransformer;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertStringLength;
use Braintacle\Validator\StringLengthValidator;
use Formotron\AssertionFailedException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewGroupFormData::class)]
#[UsesClass(AssertStringLength::class)]
#[UsesClass(StringLengthValidator::class)]
#[UsesClass(ToBool::class)]
#[UsesClass(ToBoolTransformer::class)]
#[UsesClass(TrimAndNullify::class)]
class NewGroupFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    private function getFormData(string $name, string $description): NewGroupFormData
    {
        $searchFilterValidator = $this->createMock(SearchFilterValidator::class);
        $searchFilterValidator->method('getValidationErrors')->with('_filter', [])->willReturn([]);

        $dataProcessor = $this->createDataProcessor([SearchFilterValidator::class => $searchFilterValidator]);
        $formData = $dataProcessor->process([
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'invert' => '1',
            'membershipType' => '2',
            'name' => $name,
            'description' => $description,
        ], NewGroupFormData::class);

        $this->assertEquals('_filter', $formData->filter);
        $this->assertEquals('_search', $formData->search);
        $this->assertEquals(SearchOperator::Equal, $formData->operator);
        $this->assertTrue($formData->invert);
        $this->assertEquals(Membership::Never, $formData->membershipType);

        return $formData;
    }

    public function testValid()
    {
        $max = str_repeat('Ä', 255);
        $formData = $this->getFormData(" $max ", " $max ");

        $this->assertEquals($max, $formData->name);
        $this->assertEquals($max, $formData->description);
    }

    public function testNameEmpty()
    {
        $this->expectException(AssertionFailedException::class);
        $this->getFormData('', 'description');
    }

    public function testNameWhitespaceOnly()
    {
        $this->expectException(AssertionFailedException::class);
        $this->getFormData(' ', 'description');
    }

    public function testNameTooLong()
    {
        $this->expectException(AssertionFailedException::class);
        $this->getFormData(str_repeat('x', 256), 'description');
    }

    public function testDescriptionEmpty()
    {
        $formData = $this->getFormData('new_group', '');
        $this->assertNull($formData->description);
    }

    public function testDescriptionWhitespaceOnly()
    {
        $formData = $this->getFormData('new_group', ' ');
        $this->assertNull($formData->description);
    }

    public function testDescriptionTooLong()
    {
        $this->expectException(AssertionFailedException::class);
        $this->getFormData('new_group', str_repeat('x', 256));
    }
}
