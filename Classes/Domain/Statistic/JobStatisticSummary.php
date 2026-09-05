<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Domain\Statistic;

/**
 * Views and applications of one job, as shown in the statistics module and
 * the dashboard widget.
 */
final readonly class JobStatisticSummary
{
    public function __construct(
        public int $jobUid,
        public string $title,
        /** False when the job record has been deleted but its statistics remain. */
        public bool $exists,
        /** Sum of all view counters: every page impression. */
        public int $views,
        /** Number of distinct frontend sessions that opened the job. */
        public int $sessions,
        public int $appliesStarted,
        public int $appliesCompleted,
    ) {}

    /** Share of sessions that clicked "apply", in percent. */
    public function getConversionRate(): float
    {
        if ($this->sessions === 0) {
            return 0.0;
        }

        return round($this->appliesStarted / $this->sessions * 100, 1);
    }

    public function hasData(): bool
    {
        return $this->views > 0 || $this->appliesStarted > 0 || $this->appliesCompleted > 0;
    }
}
