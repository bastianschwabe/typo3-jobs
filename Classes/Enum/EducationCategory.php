<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * credentialCategory of a schema.org EducationalOccupationalCredential.
 *
 * Google only accepts this fixed, lowercase vocabulary; free text is ignored.
 *
 * @see https://developers.google.com/search/docs/appearance/structured-data/job-posting
 */
enum EducationCategory: string
{
    case HighSchool = 'high school';
    case AssociateDegree = 'associate degree';
    case BachelorDegree = 'bachelor degree';
    case ProfessionalCertificate = 'professional certificate';
    case PostgraduateDegree = 'postgraduate degree';

    public function label(): string
    {
        return 'jobs.db:education_category.'
            . str_replace(' ', '_', $this->value);
    }

    /**
     * TCA select items, prefixed with an empty option because the field is optional.
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
