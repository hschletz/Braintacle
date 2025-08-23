<?php

namespace Braintacle\Test\Group\Add;

use Braintacle\Group\Add\NewGroupFormData;
use Braintacle\Group\Membership;
use Braintacle\Search\SearchFilterValidator;
use Braintacle\Search\SearchOperator;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Transformer\ToBool;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertStringLength;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NewGroupFormData::class)]
#[UsesClass(AssertStringLength::class)]
#[UsesClass(ToBool::class)]
#[UsesClass(TrimAndNullify::class)]
class NewGroupFormDataTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    private function getFormData(
        string $name,
        string $description,
        ?SearchFilterValidator $searchFilterValidator = null,
    ): NewGroupFormData {
        $dataProcessor = $this->createDataProcessor([
            SearchFilterValidator::class => $searchFilterValidator ?? $this->createStub(SearchFilterValidator::class),
        ]);
        $formData = $dataProcessor->process([
            'filter' => '_filter',
            'search' => '_search',
            'operator' => 'eq',
            'invert' => '1',
            'membershipType' => '2',
            'name' => $name,
            'description' => $description,
        ], NewGroupFormData::class);

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
        $this->expectException(InvalidArgumentException::class);
        $this->getFormData('', 'description');
    }

    public function testNameWhitespaceOnly()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getFormData(' ', 'description');
    }

    public function testNameTooLong()
    {
        $this->expectException(InvalidArgumentException::class);
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
        $this->expectException(InvalidArgumentException::class);
        $this->getFormData('new_group', str_repeat('x', 256));
    }

    public function testSearchFilterValidator()
    {
        $searchFilterValidator = $this->createMock(SearchFilterValidator::class);
        $searchFilterValidator->expects($this->once())->method('validate')->with('_filter', []);

        $formData = $this->getFormData('name', '', $searchFilterValidator);
        $this->assertEquals('_filter', $formData->filter);
    }
}
