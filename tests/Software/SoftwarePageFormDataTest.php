<?php

namespace Braintacle\Test\Software;

use Braintacle\Direction;
use Braintacle\Software\SoftwareFilter;
use Braintacle\Software\SoftwarePageColumn;
use Braintacle\Software\SoftwarePageFormData;
use Braintacle\Test\DataProcessorTestTrait;
use PHPUnit\Framework\TestCase;

class SoftwarePageFormDataTest extends TestCase
{
    use DataProcessorTestTrait;

    public function testDefaults()
    {
        $formData = $this->processData(
            [],
            SoftwarePageFormData::class
        );
        $this->assertEquals(SoftwareFilter::Accepted, $formData->filter);
        $this->assertEquals(SoftwarePageColumn::Name, $formData->order);
        $this->assertEquals(Direction::Ascending, $formData->direction);
    }

    public function testExplicitValues()
    {
        $formData = $this->processData(
            [
                'filter' => 'all',
                'order' => 'num_clients',
                'direction' => 'desc',
            ],
            SoftwarePageFormData::class
        );
        $this->assertEquals(SoftwareFilter::All, $formData->filter);
        $this->assertEquals(SoftwarePageColumn::NumClients, $formData->order);
        $this->assertEquals(Direction::Descending, $formData->direction);
    }
}
