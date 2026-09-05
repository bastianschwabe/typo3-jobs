<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * Employment types as defined by Google for Jobs.
 *
 * The backing values are the literal, case-sensitive schema.org values and are
 * written to the database as-is, so no mapping is needed when building JSON-LD.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
enum EmploymentType: string
{
    case FullTime = 'FULL_TIME';
    case PartTime = 'PART_TIME';
    case Contractor = 'CONTRACTOR';
    case Temporary = 'TEMPORARY';
    case Intern = 'INTERN';
    case Volunteer = 'VOLUNTEER';
    case PerDiem = 'PER_DIEM';
    case Other = 'OTHER';

    public function label(): string
    {
        return 'jobs.db:employment_type.' . $this->value;
    }

    /**
     * TCA select items for this enum.
     *
     * @return list<array{label: string, value: string}>
     */
    public static function tcaItems(): array
    {
        return array_map(
            static fn(self $case): array => ['label' => $case->label(), 'value' => $case->value],
            self::cases(),
        );
    }
}
