<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * unitText of a schema.org QuantitativeValue used inside baseSalary.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
enum SalaryUnit: string
{
    case Hour = 'HOUR';
    case Day = 'DAY';
    case Week = 'WEEK';
    case Month = 'MONTH';
    case Year = 'YEAR';

    public function label(): string
    {
        return 'jobs.db:salary_unit.' . $this->value;
    }

    /**
     * TCA select items, prefixed with an empty option because a salary is optional.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function tcaItems(): array
    {
        $items = [['label' => '', 'value' => '']];
        foreach (self::cases() as $case) {
            $items[] = ['label' => $case->label(), 'value' => $case->value];
        }

        return $items;
    }
}
