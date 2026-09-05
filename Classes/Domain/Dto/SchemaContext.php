<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Dto;

/**
 * Everything the JSON-LD needs that the job record itself cannot know:
 * absolute URLs and the site-wide organization fallback.
 */
final readonly class SchemaContext
{
    /**
     * @param list<string> $imageUrls
     */
    public function __construct(
        public string $jobUrl = '',
        public string $organizationName = '',
        public string $organizationUrl = '',
        public string $organizationLogoUrl = '',
        public array $imageUrls = [],
    ) {}
}
