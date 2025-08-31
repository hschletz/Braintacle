<?php

namespace Braintacle\Test\Group\Configuration;

use Braintacle\Group\Configuration\GroupConfigurationParameters;
use Braintacle\Test\CsrfFormProcessorTestTrait;
use Braintacle\Transformer\TrimAndNullify;
use Braintacle\Validator\AssertIpAddress;
use Braintacle\Validator\AssertNumericRange;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GroupConfigurationParameters::class)]
#[UsesClass(AssertIpAddress::class)]
#[UsesClass(AssertNumericRange::class)]
#[UsesClass(TrimAndNullify::class)]
final class GroupConfigurationParametersTest extends TestCase
{
    use CsrfFormProcessorTestTrait;

    private function createInputData(array $nonDefaultOptions)
    {
        return $nonDefaultOptions + [
            'contactInterval' => '',
            'inventoryInterval' => '',
            'downloadPeriodDelay' => '',
            'downloadCycleDelay' => '',
            'downloadFragmentDelay' => '',
            'downloadMaxPriority' => '',
            'downloadTimeout' => '',
        ];
    }

    public function testDefaults()
    {
        $input = $this->createInputData([]);
        $parameters = $this->processData($input, GroupConfigurationParameters::class);
        $this->assertNull($parameters->contactInterval);
        $this->assertNull($parameters->inventoryInterval);
        $this->assertFalse($parameters->packageDeployment);
        $this->assertNull($parameters->downloadPeriodDelay);
        $this->assertNull($parameters->downloadCycleDelay);
        $this->assertNull($parameters->downloadFragmentDelay);
        $this->assertNull($parameters->downloadMaxPriority);
        $this->assertNull($parameters->downloadTimeout);
        $this->assertFalse($parameters->allowScan);
        $this->assertFalse($parameters->scanSnmp);
    }

    public static function minValuesProvider()
    {
        return [
            ['contactInterval', 1],
            ['inventoryInterval', -1],
            ['downloadPeriodDelay', 1],
            ['downloadCycleDelay', 1],
            ['downloadFragmentDelay', 1],
            ['downloadMaxPriority', 0],
            ['downloadTimeout', 1],
        ];
    }

    #[DataProvider('minValuesProvider')]
    public function testMinValues(string $option, int $minValue)
    {
        $input = $this->createInputData([$option => (string) $minValue]);
        $parameters = $this->processData($input, GroupConfigurationParameters::class);
        $this->assertEquals($minValue, $parameters->$option);
    }


    #[DataProvider('minValuesProvider')]
    public function testMinValuesTooSmall(string $option, int $minValue)
    {
        $inputValue = $minValue - 1;
        $this->expectExceptionMessage("Value $inputValue is less than $minValue");

        $input = $this->createInputData([$option => (string) $inputValue]);
        $this->processData($input, GroupConfigurationParameters::class);
    }

    public function testDownloadMaxPriorityMax()
    {
        $input = $this->createInputData(['downloadMaxPriority' => '10']);
        $parameters = $this->processData($input, GroupConfigurationParameters::class);
        $this->assertEquals(10, $parameters->downloadMaxPriority);
    }

    public function testDownloadMaxPriorityMaxExceeded()
    {
        $this->expectExceptionMessage("Value 11 is greater than 10");
        $input = $this->createInputData(['downloadMaxPriority' => '11']);
        $this->processData($input, GroupConfigurationParameters::class);
    }

    public function testBoolValuesSet()
    {
        $input = $this->createInputData([
            'packageDeployment' => 'on',
            'allowScan' => 'on',
            'scanSnmp' => 'on',
        ]);
        $parameters = $this->processData($input, GroupConfigurationParameters::class);
        $this->assertTrue($parameters->packageDeployment);
        $this->assertTrue($parameters->allowScan);
        $this->assertTrue($parameters->scanSnmp);
    }
}
