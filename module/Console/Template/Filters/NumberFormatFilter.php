<?php

namespace Console\Template\Filters;

use Locale;
use NumberFormatter;

/**
 * Format numeric value according to current locale.
 */
class NumberFormatFilter
{
    /**
     * @var array<string, NumberFormatter>
     */
    private $numberFormatters = [];

    public function __invoke($value, int $fractionDigits): string
    {
        $locale = Locale::getDefault();

        // Once created, the locale cannot be changed. Instantiate a new
        // formatter for each locale. This has to be done at invocation time,
        // not in the constructor, because the requested locale may not be known
        // in advance in case of a cached template.
        if (!isset($this->numberFormatters[$locale])) {
            $this->numberFormatters[$locale] = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        }
        $numberFormatter = $this->numberFormatters[$locale];
        $numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
        $numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);

        return $numberFormatter->format($value, NumberFormatter::TYPE_DOUBLE);
    }
}
