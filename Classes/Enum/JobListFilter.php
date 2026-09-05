<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * Filter of the "Jobs" backend module. "Active" and "inactive" are decided
 * by the access fields alone (hidden, starttime, endtime); an open-ended or
 * expired validThrough date does not change the access state.
 */
enum JobListFilter: string
{
    case All = 'all';
    case Active = 'active';
    case Inactive = 'inactive';
}
