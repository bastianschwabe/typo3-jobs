<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * How far an application got.
 *
 * The proxy only ever writes STARTED. The other values are prepared for an
 * own application form, which can report the outcome of the same session.
 */
enum ApplyStatus: string
{
    case Started = 'STARTED';
    case Completed = 'COMPLETED';
    case Aborted = 'ABORTED';
}
