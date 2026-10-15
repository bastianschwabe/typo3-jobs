<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * Where the "apply" proxy sent the visitor.
 *
 * FORM is the EXT:form application form the "jobs.applicationFormPid" site
 * setting points to.
 */
enum ApplyType: string
{
    case Url = 'URL';
    case Email = 'EMAIL';
    case Form = 'FORM';
}
