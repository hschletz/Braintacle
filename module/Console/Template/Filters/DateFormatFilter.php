<?php

namespace Console\Template\Filters;

use IntlDateFormatter;
use Locale;
use RuntimeException;

/**
 * Format date/time value according to current locale.
 */
class DateFormatFilter
{
    /**
     * @var array<string, array<int, array<int, IntlDateFormatter>>>
     */
    private $dateFormatters = [];

    public function __invoke($value, $dateFormat, $timeFormat = IntlDateFormatter::NONE): string
    {
        $locale = Locale::getDefault();

        // Once created, the locale and formats cannot be changed. Instantiate a
        // new formatter for each locale/format combination. This has to be done
        // at invocation time, not in the constructor, because the requested
        // locale may not be known in advance in case of a cached template.
        if (!isset($this->dateFormatters[$locale][$dateFormat][$timeFormat])) {
            $this->dateFormatters[$locale][$dateFormat][$timeFormat] = new IntlDateFormatter(
                $locale,
                $dateFormat,
                $timeFormat
            );
        }
        $dateFormatter = $this->dateFormatters[$locale][$dateFormat][$timeFormat];

        $formatted = $dateFormatter->format($value);
        if (!$formatted) {
            throw new RuntimeException($dateFormatter->getErrorMessage());
        }

        return $formatted;
    }
}
