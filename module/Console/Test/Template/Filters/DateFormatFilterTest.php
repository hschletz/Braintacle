<?php

namespace Console\test\Template\Filters;

use Console\Template\Filters\DateFormatFilter;
use DateTime;
use IntlDateFormatter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class DateFormatFilterTest extends TestCase
{
    public function testValidValues()
    {
        $date = new DateTime('2022-10-14T17:40:33');
        $filter = new DateFormatFilter();

        $this->assertEquals('14.10.2022', $filter($date, IntlDateFormatter::MEDIUM));
        $this->assertEquals('14.10.2022', $filter($date, IntlDateFormatter::MEDIUM)); // repeat for cache reuse
        $this->assertEquals('14.10.22, 17:40', $filter($date, IntlDateFormatter::SHORT, IntlDateFormatter::SHORT));
    }

    public function testInvalidValue()
    {
        $this->expectException(RuntimeException::class);
        $filter = new DateFormatFilter();
        $filter(null, IntlDateFormatter::SHORT);
    }
}
